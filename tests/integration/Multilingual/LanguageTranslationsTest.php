<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Multilingual;

use ItalyCookieChoices\Multilingual\Infrastructure\ActiveLanguageTranslations;
use ItalyCookieChoices\Multilingual\Infrastructure\PolylangTranslations;
use ItalyCookieChoices\Multilingual\Infrastructure\WpmlTranslations;
use ItalyStrap\Event\GlobalDispatcher;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class LanguageTranslationsTest extends WPTestCase
{
    private const POLICY = 'https://example.com/policy/';
    private const GERMAN_POLICY = 'https://example.com/de/policy/';

    public function setUp(): void
    {
        parent::setUp();
        require_once \codecept_root_dir('tests/_support/stubs/wpml-polylang.php');
    }

    public function tearDown(): void
    {
        unset(
            $GLOBALS['icc_wpml_string_ids'],
            $GLOBALS['icc_wpml_saved'],
            $GLOBALS['icc_pll_languages'],
            $GLOBALS['icc_pll_posts'],
            $GLOBALS['icc_pll_mo']
        );
        parent::tearDown();
    }

    public function testWpmlGivesItsLanguagesAndTranslations(): void
    {
        $this->wpml();
        $sut = new WpmlTranslations(new GlobalDispatcher());

        $this->assertTrue($sut->isActive());
        $this->assertSame(
            [
                ['code' => 'it', 'name' => 'Italiano', 'default' => true],
                ['code' => 'de', 'name' => 'Deutsch', 'default' => false],
            ],
            $sut->languages()
        );
        $this->assertSame(42, $sut->translatedPost(7, 'de'));
        $this->assertSame(0, $sut->translatedPost(8, 'de'));
        $this->assertSame('de: Banner url', $sut->translatedString('Banner url', self::POLICY, 'de'));
    }

    public function testWpmlSavesTheTranslationOfARegisteredString(): void
    {
        $this->wpml();
        $GLOBALS['icc_wpml_string_ids'] = ['Italy Cookie Choices:Banner url' => 5];

        (new WpmlTranslations(new GlobalDispatcher()))
            ->saveStringTranslation('Banner url', self::POLICY, 'de', self::GERMAN_POLICY);

        $this->assertSame([[5, 'de', self::GERMAN_POLICY, 10]], $GLOBALS['icc_wpml_saved']);
    }

    public function testWpmlCannotSaveAStringNotRegistered(): void
    {
        $this->wpml();

        $this->expectException(\RuntimeException::class);

        (new WpmlTranslations(new GlobalDispatcher()))
            ->saveStringTranslation('Banner url', self::POLICY, 'de', self::GERMAN_POLICY);
    }

    public function testPolylangGivesItsLanguagesAndTranslationsAndSavesTheStrings(): void
    {
        $GLOBALS['icc_pll_languages'] = ['it' => 'Italiano', 'de' => 'Deutsch'];
        $GLOBALS['icc_pll_posts'] = ['7:de' => 42];
        $GLOBALS['icc_pll_mo'] = ['de' => ['Other' => 'Andere']];
        $sut = new PolylangTranslations();

        $this->assertTrue($sut->isActive());
        $this->assertSame(
            [
                ['code' => 'it', 'name' => 'Italiano', 'default' => true],
                ['code' => 'de', 'name' => 'Deutsch', 'default' => false],
            ],
            $sut->languages()
        );
        $this->assertSame(42, $sut->translatedPost(7, 'de'));
        $this->assertSame(self::POLICY, $sut->translatedString('Banner url', self::POLICY, 'de'));

        $sut->saveStringTranslation('Banner url', self::POLICY, 'de', self::GERMAN_POLICY);

        $this->assertSame(
            ['de' => ['Other' => 'Andere', self::POLICY => self::GERMAN_POLICY]],
            $GLOBALS['icc_pll_mo']
        );
        $this->assertSame(self::GERMAN_POLICY, $sut->translatedString('Banner url', self::POLICY, 'de'));
    }

    public function testWpmlComesBeforePolylang(): void
    {
        $GLOBALS['icc_pll_languages'] = ['en' => 'English'];
        $sut = new ActiveLanguageTranslations(new WpmlTranslations(new GlobalDispatcher()), new PolylangTranslations());

        $this->assertSame('en', $sut->languages()[0]['code']);

        $this->wpml();
        $this->assertSame('it', $sut->languages()[0]['code']);
    }

    private function wpml(): void
    {
        \add_filter('wpml_active_languages', static fn(): array => [
            'it' => ['code' => 'it', 'native_name' => 'Italiano'],
            'de' => ['code' => 'de', 'native_name' => 'Deutsch'],
        ]);
        \add_filter('wpml_default_language', static fn(): string => 'it');
        \add_filter(
            'wpml_object_id',
            static fn(mixed $id, mixed $type, mixed $original, mixed $language): ?int =>
                $id === 7 && $language === 'de' ? 42 : null,
            10,
            4
        );
        \add_filter(
            'wpml_translate_single_string',
            static fn(mixed $text, mixed $context, mixed $name, mixed $language): string =>
                \sprintf('%s: %s', \is_string($language) ? $language : '', \is_string($name) ? $name : ''),
            10,
            4
        );
    }
}
