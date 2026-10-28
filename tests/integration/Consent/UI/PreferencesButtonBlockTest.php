<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\UI;

use ItalyCookieChoices\Consent\UI\PreferencesButton;
use ItalyCookieChoices\Consent\UI\PreferencesButtonBlock;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class PreferencesButtonBlockTest extends WPTestCase
{
    use BuildsOptions;

    private const LANGUAGES = '/plugin/languages';

    public function setUp(): void
    {
        parent::setUp();
        $this->unregister();
        \update_option(Schema::OPTION, ['active' => '1']);
    }

    public function tearDown(): void
    {
        $this->unregister();
        parent::tearDown();
    }

    public function testItRegistersOnInit(): void
    {
        $this->assertSame(['init' => 'register'], \iterator_to_array($this->makeSut()->getSubscribedEvents()));
    }

    public function testItRegistersTheBlockFromItsMetadata(): void
    {
        $this->makeSut()->register();

        $blockType = \WP_Block_Type_Registry::get_instance()->get_registered(PreferencesButtonBlock::NAME);
        $this->assertInstanceOf(\WP_Block_Type::class, $blockType);
        $this->assertSame(['label' => ['type' => 'string', 'default' => '', 'role' => 'content']], \array_intersect_key(
            $blockType->attributes ?? [],
            ['label' => true]
        ));
        $this->assertSame(['italy-cookie-choices-preferences-button-editor-script'], $blockType->editor_script_handles);
    }

    public function testTheEditorScriptUsesTheBundledTranslations(): void
    {
        $this->makeSut()->register();

        $script = \wp_scripts()->registered['italy-cookie-choices-preferences-button-editor-script'] ?? null;
        $this->assertInstanceOf(\_WP_Dependency::class, $script);
        $this->assertSame('italy-cookie-choices', $script->textdomain);
        $this->assertSame(self::LANGUAGES, $script->translations_path);
    }

    public function testNothingIsRegisteredWhenTheAssetsAreNotBuilt(): void
    {
        $this->makeSut('/not/built/')->register();

        $this->assertFalse(\WP_Block_Type_Registry::get_instance()->is_registered(PreferencesButtonBlock::NAME));
    }

    public function testItPrintsTheButtonWithTheDefaultLabel(): void
    {
        $this->makeSut()->register();

        $this->assertSame(
            '<button type="button" class="icc-preferences-button wp-element-button '
            . 'wp-block-italy-cookie-choices-preferences-button" data-icc-open-preferences>Cookie preferences</button>',
            \render_block(['blockName' => PreferencesButtonBlock::NAME, 'attrs' => [], 'innerBlocks' => []])
        );
    }

    public function testItPrintsTheSavedLabelAsText(): void
    {
        $this->makeSut()->register();

        $output = \render_block([
            'blockName' => PreferencesButtonBlock::NAME,
            'attrs' => ['label' => 'Change <b>your</b> choice & more'],
            'innerBlocks' => [],
        ]);

        $this->assertStringContainsString('>Change your choice &amp; more</button>', $output);
    }

    public function testAMalformedLabelFallsBackToTheDefault(): void
    {
        $this->assertStringContainsString('>Cookie preferences</button>', $this->makeSut()->render(['label' => ['a']]));
        $this->assertStringContainsString('>Cookie preferences</button>', $this->makeSut()->render(['label' => '  ']));
    }

    public function testItPrintsTheSupportsOfTheBlock(): void
    {
        $this->makeSut()->register();

        $output = \render_block([
            'blockName' => PreferencesButtonBlock::NAME,
            'attrs' => ['anchor' => 'preferences', 'style' => ['color' => ['text' => '#ff0000']]],
            'innerBlocks' => [],
        ]);

        $this->assertStringContainsString('color:#ff0000', $output);
    }

    public function testNothingIsPrintedWhenThePluginIsNotActive(): void
    {
        \update_option(Schema::OPTION, []);

        $this->assertSame('', $this->makeSut()->render([]));
    }

    private function unregister(): void
    {
        $registry = \WP_Block_Type_Registry::get_instance();
        if (!$registry->is_registered(PreferencesButtonBlock::NAME)) {
            return;
        }

        $registry->unregister(PreferencesButtonBlock::NAME);
    }

    private function makeSut(string $buildPath = ''): PreferencesButtonBlock
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $this->assertIsString($fixtures);

        return new PreferencesButtonBlock(
            new PreferencesButton(),
            $this->options(),
            $buildPath === '' ? $fixtures : $buildPath,
            self::LANGUAGES
        );
    }
}
