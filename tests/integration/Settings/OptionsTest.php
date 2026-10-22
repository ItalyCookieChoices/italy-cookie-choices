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

    public function testACheckboxIsOnWhenItIsSaved(): void
    {
        \update_option(Schema::OPTION, ['target' => '1']);

        $this->assertTrue((new Options())->isChecked('target'));
        $this->assertFalse((new Options())->isChecked('nofollow'));
    }

    public function testATextFallsBackToTheDefault(): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(Schema::OPTION, ['url' => 'https://example.com/', 'slug' => '', 'banner' => 2]);

        $sut = new Options();

        $this->assertSame('https://example.com/', $sut->text('url'));
        $this->assertSame('policy', $sut->text('slug', 'policy'));
        $this->assertSame('1', $sut->text('banner', '1'));
        $this->assertSame('', $sut->text('missing'));
    }
}
