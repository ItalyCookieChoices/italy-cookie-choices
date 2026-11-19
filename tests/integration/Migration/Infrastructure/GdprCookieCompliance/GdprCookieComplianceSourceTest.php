<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Infrastructure\GdprCookieCompliance;

use ItalyCookieChoices\Migration\Infrastructure\GdprCookieCompliance\GdprCookieComplianceSource;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Migration\GdprCookieComplianceFixture;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class GdprCookieComplianceSourceTest extends WPTestCase
{
    use BuildsContainer;

    private const SETTINGS = [
        'moove_gdpr_infobar_visibility' => 'visible',
        'moove_gdpr_info_bar_content' => '<p>We use cookies. Change them in [setting]settings[/setting].</p>',
        'moove_gdpr_infobar_accept_button_label' => 'Accept',
        'moove_gdpr_infobar_reject_button_label' => 'Reject',
        'moove_gdpr_infobar_settings_button_label' => 'Settings',
        'moove_gdpr_modal_save_button_label' => 'Save Changes',
        'moove_gdpr_infobar_position' => 'bottom_right',
        'moove_gdpr_colour_scheme' => '2',
        'moove_gdpr_company_logo_id' => '42',
        'moove_gdpr_consent_expiration' => '120',
        'gdpr_force_reload' => '1',
        'moove_gdpr_floating_button_enable' => '1',
        'moove_gdpr_floating_button_label' => 'Change cookie settings',
        'moove_gdpr_strictly_necessary_cookies_tab_title' => 'Necessary',
        'moove_gdpr_strict_necessary_cookies_tab_content' => '<p>Always on.</p>',
        'moove_gdpr_third_party_cookies_enable' => '1',
        'moove_gdpr_performance_cookies_tab_title' => 'Analytics',
        'moove_gdpr_performance_cookies_tab_content' => '<p>Visits.</p>',
        'moove_gdpr_advanced_cookies_enable' => '1',
        'moove_gdpr_advanced_cookies_tab_title' => 'Marketing',
        'moove_gdpr_advanced_cookies_tab_content' => '<p>Ads.</p>',
        'moove_gdpr_third_party_header_scripts' => '<script>analytics();</script>',
        'moove_gdpr_advanced_cookies_footer_scripts' => '<img src="https://ads.example.com/pixel.gif" alt="">',
        'gdin_values' => '{"ga4":"2","ga4_id":"G-ICC123","gtmc2":"2","gtmc2_id":"GTM-ICC",'
            . '"fbp":"3","fbp_id":"123456789012345","gadc":"3","gadc_id":"AW-123"}',
    ];

    public function testItIsFoundOnlyWithTheSettingsOfGdprCookieCompliance(): void
    {
        $this->assertFalse($this->sut()->isFound());

        GdprCookieComplianceFixture::install(self::SETTINGS);

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('gdpr-cookie-compliance', $this->sut()->id());
        $this->assertSame('GDPR Cookie Compliance', $this->sut()->name());
    }

    public function testItImportsTheBanner(): void
    {
        GdprCookieComplianceFixture::install(self::SETTINGS);

        $settings = $this->sut()->read()->settings();

        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('<p>We use cookies. Change them in settings.</p>', $settings['text'] ?? null);
        $this->assertSame('Accept', $settings['button_text'] ?? null);
        $this->assertSame('Reject', $settings['reject_button_text'] ?? null);
        $this->assertSame('Settings', $settings['preferences_button_text'] ?? null);
        $this->assertSame('Save Changes', $settings['save_button_text'] ?? null);
        $this->assertSame('5', $settings['banner'] ?? null);
        $this->assertSame('#ffffff', $settings['banner_bg'] ?? null);
        $this->assertSame('#202020', $settings['banner_text_color'] ?? null);
        $this->assertSame('42', $settings['banner_logo'] ?? null);
        $this->assertSame('90', $settings['consent_duration'] ?? null);
        $this->assertSame('1', $settings['reload'] ?? null);
        $this->assertSame('1', $settings['floating_button'] ?? null);
        $this->assertSame('left', $settings['floating_button_position'] ?? null);
        $this->assertSame('Change cookie settings', $settings['floating_button_label'] ?? null);
        $this->assertSame(
            [
                'necessary' => ['label' => 'Necessary', 'description' => 'Always on.'],
                'statistics' => ['label' => 'Analytics', 'description' => 'Visits.'],
                'marketing' => ['label' => 'Marketing', 'description' => 'Ads.'],
            ],
            $settings['categories'] ?? null
        );
    }

    public function testItImportsTheScriptsAndTheTags(): void
    {
        GdprCookieComplianceFixture::install(self::SETTINGS);

        $imported = $this->sut()->read();
        $settings = $imported->settings();

        $this->assertSame(
            [
                [
                    'name' => 'Analytics (head)',
                    'category' => 'statistics',
                    'position' => 'head',
                    'code' => '<script>analytics();</script>',
                ],
                [
                    'name' => 'Marketing (footer)',
                    'category' => 'marketing',
                    'position' => 'body',
                    'code' => '<img src="https://ads.example.com/pixel.gif" alt="">',
                ],
            ],
            $settings['custom_scripts'] ?? null
        );
        $this->assertSame('G-ICC123', $settings['ga_measurement_id'] ?? null);
        $this->assertSame('GTM-ICC', $settings['gtm_container_id'] ?? null);
        $this->assertSame('123456789012345', $settings['facebook_pixel_id'] ?? null);
        $this->assertSame('1', $settings['consent_mode'] ?? null);
        $this->assertSame(['google-ads'], $imported->vendorsInUse());
        $this->assertStringContainsString('AW-123', \implode("\n", $imported->notes()));
    }

    public function testTheTextsOfTheDefaultLanguageOfPolylangWin(): void
    {
        GdprCookieComplianceFixture::install([
            ...self::SETTINGS,
            'moove_gdpr_info_bar_content_it' => 'Usiamo i cookie.',
        ]);
        \update_option('polylang', ['default_lang' => 'it']);

        $this->assertSame('Usiamo i cookie.', $this->sut()->read()->settings()['text'] ?? null);
    }

    public function testAHiddenBannerKeepsTheFloatingButton(): void
    {
        GdprCookieComplianceFixture::install([
            'moove_gdpr_infobar_visibility' => 'hidden',
            'moove_gdpr_consent_expiration' => '0',
        ]);

        $settings = $this->sut()->read()->settings();

        $this->assertArrayNotHasKey('active', $settings);
        $this->assertSame('1', $settings['floating_button'] ?? null);
        $this->assertSame('30', $settings['consent_duration'] ?? null);
    }

    public function testItReadsTheOptionOfTheVersionsBeforeTheTable(): void
    {
        \update_option('moove_gdpr_plugin_settings', ['moove_gdpr_infobar_accept_button_label' => 'Ok']);

        $this->assertSame('Ok', $this->sut()->read()->settings()['button_text'] ?? null);
    }

    private function sut(): GdprCookieComplianceSource
    {
        return $this->service(GdprCookieComplianceSource::class);
    }
}
