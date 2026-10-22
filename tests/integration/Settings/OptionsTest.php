<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class OptionsTest extends WPTestCase
{
    public function testThePluginIsActiveWhenTheCheckboxIsSaved(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $this->assertTrue((new Options())->isActive());
    }

    public function testThePluginIsNotActiveByDefault(): void
    {
        \delete_option(Schema::OPTION);

        $this->assertFalse((new Options())->isActive());
    }

    public function testTheLegacyCookieIsTheSavedOne(): void
    {
        \update_option(Schema::OPTION, ['cookie_name' => 'accepted', 'cookie_value' => 'yes']);

        $this->assertSame(['name' => 'accepted', 'value' => 'yes'], (new Options())->legacyCookie());
    }

    public function testTheLegacyCookieFallsBackToTheDefaults(): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(Schema::OPTION, ['cookie_name' => '', 'cookie_value' => ['y']]);

        $this->assertSame(['name' => 'displayCookieConsent', 'value' => 'y'], (new Options())->legacyCookie());
    }

    public function testAnOptionThatIsNotAnArrayIsEmpty(): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(Schema::OPTION, 'invalid');

        $this->assertFalse((new Options())->isActive());
        $this->assertSame(['name' => 'displayCookieConsent', 'value' => 'y'], (new Options())->legacyCookie());
    }
}
