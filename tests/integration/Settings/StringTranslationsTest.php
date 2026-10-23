<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\StringTranslations;
use ItalyCookieChoices\Settings\TranslatingText;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalDispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class StringTranslationsTest extends WPTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        require_once \codecept_root_dir('tests/_support/stubs/multilingual.php');
        $GLOBALS['icc_pll_strings'] = [];
        $GLOBALS['icc_pll_translations'] = [];
    }

    public function tearDown(): void
    {
        unset($GLOBALS['icc_pll_strings'], $GLOBALS['icc_pll_translations']);
        parent::tearDown();
    }

    public function testATextIsRegisteredInWpmlAndPolylang(): void
    {
        $wpml = [];
        \add_action(
            'wpml_register_single_string',
            static function (string $context, string $name, string $value) use (&$wpml): void {
                $wpml[] = [$context, $name, $value];
            },
            10,
            3
        );

        $this->makeSut()->register('Banner text', 'Policy text', true);

        $this->assertSame([['Italy Cookie Choices', 'Banner text', 'Policy text']], $wpml);
        $this->assertSame(
            [['Banner text', 'Policy text', 'Italy Cookie Choices', true]],
            $GLOBALS['icc_pll_strings']
        );
    }

    public function testWpmlTranslatesByTheName(): void
    {
        \add_filter(
            'wpml_translate_single_string',
            static fn(string $text, string $context, string $name): string => $context . ': ' . $name,
            10,
            3
        );

        $this->assertSame(
            'Italy Cookie Choices: Banner text',
            $this->makeSut()->translate('Banner text', 'Policy text')
        );
    }

    /**
     * The 2.x versions passed the name to pll__(), so Polylang never translated the texts (#181).
     */
    public function testPolylangTranslatesByTheText(): void
    {
        $GLOBALS['icc_pll_translations'] = ['Policy text' => 'Testo della policy'];

        $this->assertSame('Testo della policy', $this->makeSut()->translate('Banner text', 'Policy text'));
    }

    public function testTheListenersTranslateTheText(): void
    {
        \add_action(TranslatingText::class, static function (TranslatingText $event): void {
            $event->changeText('[:it]' . $event->name() . ': ' . $event->text());
        });

        $this->assertSame('[:it]Banner text: Policy text', $this->makeSut()->translate('Banner text', 'Policy text'));
    }

    private function makeSut(): StringTranslations
    {
        return new StringTranslations(
            new GlobalDispatcher(),
            new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState())
        );
    }
}
