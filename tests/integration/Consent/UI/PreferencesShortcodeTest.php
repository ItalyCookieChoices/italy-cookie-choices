<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\UI;

use ItalyCookieChoices\Consent\UI\PreferencesShortcode;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class PreferencesShortcodeTest extends WPTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        \remove_shortcode(PreferencesShortcode::TAG);
        \remove_shortcode(PreferencesShortcode::LEGACY_TAG);
        \update_option(Schema::OPTION, ['active' => '1']);
    }

    public function testItRegistersOnInit(): void
    {
        $this->assertSame(['init' => 'register'], \iterator_to_array($this->makeSut()->getSubscribedEvents()));
    }

    public function testTheButtonOpensThePreferences(): void
    {
        $this->makeSut()->register();

        $this->assertSame(
            '<button type="button" class="icc-preferences-button" data-icc-open-preferences>Cookie preferences</button>',
            \do_shortcode('[cookie_preferences]')
        );
    }

    public function testTheTextIsEscaped(): void
    {
        $this->makeSut()->register();

        $this->assertStringContainsString(
            '>Change &lt;b&gt;your&lt;/b&gt; choice</button>',
            \do_shortcode('[cookie_preferences text="Change <b>your</b> choice"]')
        );
    }

    /**
     * Changed in #237: the 2.x [delete_cookie] button never worked, it opens the preferences now.
     */
    public function testTheLegacyShortcodeOpensThePreferences(): void
    {
        $this->makeSut()->register();

        $this->assertStringContainsString('data-icc-open-preferences', \do_shortcode('[delete_cookie]'));
    }

    public function testNothingIsRegisteredWhenThePluginIsNotActive(): void
    {
        \update_option(Schema::OPTION, []);

        $this->makeSut()->register();

        $this->assertFalse(\shortcode_exists(PreferencesShortcode::TAG));
        $this->assertFalse(\shortcode_exists(PreferencesShortcode::LEGACY_TAG));
    }

    public function testTheAttributesCanBeAString(): void
    {
        $this->assertStringContainsString('>Cookie preferences</button>', $this->makeSut()->render(''));
    }

    private function makeSut(): PreferencesShortcode
    {
        return new PreferencesShortcode(new Options());
    }
}
