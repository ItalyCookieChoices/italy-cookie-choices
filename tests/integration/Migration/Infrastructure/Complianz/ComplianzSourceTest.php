<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Infrastructure\Complianz;

use ItalyCookieChoices\Migration\Application\MigrationSources;
use ItalyCookieChoices\Migration\Infrastructure\Complianz\ComplianzSource;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Migration\ComplianzFixture;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ComplianzSourceTest extends WPTestCase
{
    use BuildsContainer;

    private const OPTIONS = [
        'enable_cookie_banner' => 'yes',
        'cookie-statement' => 'custom',
        'impressum' => 'url',
        'cookie_expiry' => 200,
        'cookie_domain' => 'https://example.com/',
        'respect_dnt' => 'yes',
        'consent_per_service' => 'yes',
        'consent-mode' => 'yes',
        'gtag-basic-consent-mode' => 'no',
        'compile_statistics' => 'google-analytics',
        'configuration_by_complianz' => 'yes',
        'ua_code' => 'G-ICC123',
        'uses_thirdparty_services' => 'yes',
        'thirdparty_services_on_site' => ['youtube', 'google-recaptcha', 'activecampaign'],
        'uses_social_media' => 'yes',
        'socialmedia_on_site' => ['twitter'],
        'blocked_content_text' => 'Accept the cookies to see this content',
        'agree_text_per_service' => 'Show it',
        'regions' => ['eu', 'us'],
    ];

    private const BANNER = [
        'position' => 'bottom-left',
        'use_logo' => 'custom',
        'logo_attachment_id' => '42',
        'revoke' => 'Manage consent',
        'manage_consent_options' => 'show-everywhere',
        'accept' => 'Accept',
        'dismiss' => ['text' => 'Deny', 'show' => true],
        'view_preferences' => 'View preferences',
        'save_preferences' => 'Save my preferences',
        'message_optin' => 'We use <strong>cookies</strong>.',
        'category_functional' => 'Functional',
        'functional_text' => ['text' => 'Always needed.', 'show' => true],
        'category_stats' => ['text' => 'Statistics', 'show' => true],
        'statistics_text' => ['text' => 'Count the visits.', 'show' => true],
        'category_all' => ['text' => 'Marketing', 'show' => true],
        'marketing_text' => ['text' => 'Ads.', 'show' => true],
        'disable_cookiebanner' => '',
        'soft_cookiewall' => '',
        'dismiss_on_scroll' => '1',
        'use_custom_cookie_css' => '1',
        'custom_css' => '.cmplz-cookiebanner { color: red; } /* mine */',
        'colorpalette_background' => ['color' => '#123456', 'border' => '#000000'],
        'colorpalette_text' => ['color' => '#fafafa', 'hyperlink' => '#ffffff'],
    ];

    public function testItIsFoundOnlyWithTheSettingsOfComplianz(): void
    {
        $this->assertFalse($this->sut()->isFound());

        ComplianzFixture::install(self::OPTIONS, self::BANNER);

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('complianz', $this->sut()->id());
        $this->assertSame('Complianz', $this->sut()->name());
    }

    public function testBeforeTheWizardThereIsNothingToImport(): void
    {
        ComplianzFixture::install(['use_cdb_api' => 'yes']);
        ComplianzFixture::removeBanners();

        $ids = \array_map(
            static fn($source): string => $source->id(),
            $this->service(MigrationSources::class)->found()
        );

        $this->assertTrue($this->sut()->read()->isEmpty());
        $this->assertNotContains('complianz', $ids);
    }

    public function testItImportsTheBannerOfTheDefaultBanner(): void
    {
        ComplianzFixture::install(self::OPTIONS, self::BANNER);

        $settings = $this->sut()->read()->settings();

        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('We use <strong>cookies</strong>.', $settings['text'] ?? null);
        $this->assertSame('Accept', $settings['button_text'] ?? null);
        $this->assertSame('Deny', $settings['reject_button_text'] ?? null);
        $this->assertSame('View preferences', $settings['preferences_button_text'] ?? null);
        $this->assertSame('Save my preferences', $settings['save_button_text'] ?? null);
        $this->assertSame('4', $settings['banner'] ?? null);
        $this->assertSame('42', $settings['banner_logo'] ?? null);
        $this->assertSame('#123456', $settings['banner_bg'] ?? null);
        $this->assertSame('#fafafa', $settings['banner_text_color'] ?? null);
        $this->assertSame('1', $settings['floating_button'] ?? null);
        $this->assertSame('left', $settings['floating_button_position'] ?? null);
        $this->assertSame('Manage consent', $settings['floating_button_label'] ?? null);
        $this->assertSame(
            [
                'necessary' => ['label' => 'Functional', 'description' => 'Always needed.'],
                'statistics' => ['label' => 'Statistics', 'description' => 'Count the visits.'],
                'marketing' => ['label' => 'Marketing', 'description' => 'Ads.'],
            ],
            $settings['categories'] ?? null
        );
        $css = $settings['customCSS'] ?? null;
        $this->assertIsString($css);
        $this->assertStringContainsString('.cmplz-cookiebanner { color: red; } /* mine * /', $css);
        $this->assertStringStartsWith('/*', $css);
    }

    public function testItImportsTheGeneralSettings(): void
    {
        ComplianzFixture::install(self::OPTIONS, self::BANNER);
        \update_option('cmplz_excluded_posts_array', ['contact', 'about']);

        $settings = $this->sut()->read()->settings();

        $this->assertSame('182', $settings['consent_duration'] ?? null);
        $this->assertSame('example.com', $settings['cookie_domain'] ?? null);
        $this->assertSame('1', $settings['global_privacy_control'] ?? null);
        $this->assertSame('1', $settings['vendor_consent'] ?? null);
        $this->assertSame('1', $settings['consent_mode'] ?? null);
        $this->assertSame('1', $settings['consent_mode_advanced'] ?? null);
        $this->assertSame('G-ICC123', $settings['ga_measurement_id'] ?? null);
        $this->assertSame('contact, about', $settings['excluded_pages'] ?? null);
        $this->assertSame('Accept the cookies to see this content', $settings['content_message_text'] ?? null);
        $this->assertSame('Show it', $settings['content_message_button_text'] ?? null);
    }

    public function testASettingComplianzLeavesOffKeepsTheDefaultOfCookieChoices(): void
    {
        ComplianzFixture::install(['enable_cookie_banner' => 'yes', 'respect_dnt' => 'no', 'consent-mode' => 'no']);

        $settings = $this->sut()->read()->settings();

        $this->assertArrayNotHasKey('global_privacy_control', $settings);
        $this->assertArrayNotHasKey('consent_mode', $settings);
        $this->assertArrayNotHasKey('ga_measurement_id', $settings);
    }

    public function testItImportsThePolicyPageAndTheLegalNoticeLink(): void
    {
        $policy = self::factory()->post->create([
            'post_type' => 'page',
            'post_name' => 'cookie-policy',
            'post_title' => 'Cookie policy',
        ]);
        $this->assertIsInt($policy);
        ComplianzFixture::install(self::OPTIONS, self::BANNER);
        \update_option('cmplz_cookie-statement_custom_page', $policy);
        \update_option('cmplz_impressum_custom_page_url', 'https://example.com/impressum/');

        $settings = $this->sut()->read()->settings();

        $this->assertSame('cookie-policy', $settings['slug'] ?? null);
        $this->assertSame((string) \get_permalink($policy), $settings['url'] ?? null);
        $this->assertSame('Cookie policy', $settings['anchor_text'] ?? null);
        $this->assertSame('https://example.com/impressum/', $settings['legal_notice_url'] ?? null);
    }

    public function testItTurnsOnThePresetsOfTheServicesInUse(): void
    {
        ComplianzFixture::install(self::OPTIONS, self::BANNER);

        $this->assertSame(
            ['google-analytics', 'youtube', 'google-recaptcha', 'x'],
            $this->sut()->read()->vendorsInUse()
        );
    }

    public function testItImportsTheScriptCenter(): void
    {
        ComplianzFixture::install(self::OPTIONS, self::BANNER);
        \update_option('complianz_options_custom-scripts', [
            'block_script' => [
                [
                    'enable' => 1,
                    'name' => 'Chat',
                    'urls' => ['chat.example.com/widget.js'],
                    'category' => 'preferences',
                ],
                ['enable' => 0, 'name' => 'Off', 'urls' => ['off.example.com'], 'category' => 'marketing'],
                ['enable' => 1, 'name' => 'Needed', 'urls' => ['needed.example.com'], 'category' => 'functional'],
            ],
            'add_script' => [
                ['enable' => 1, 'name' => 'Tracker', 'editor' => 'track();', 'category' => 'statistics'],
            ],
            'whitelist_script' => [
                ['enable' => 1, 'name' => 'Public', 'urls' => ['youtube.com/embed/public']],
            ],
        ]);

        $settings = $this->sut()->read()->settings();

        $this->assertSame(
            [
                [
                    'id' => 'chat',
                    'name' => 'Chat',
                    'category' => 'preferences',
                    'patterns' => ['chat.example.com/widget.js'],
                ],
                ['id' => 'needed', 'name' => 'Needed', 'category' => 'necessary', 'patterns' => ['needed.example.com']],
            ],
            $settings['custom_vendors'] ?? null
        );
        $this->assertSame(
            [[
                'name' => 'Tracker',
                'category' => 'statistics',
                'position' => 'body',
                'code' => "<script>\ntrack();\n</script>",
            ]],
            $settings['custom_scripts'] ?? null
        );
        $this->assertSame(['youtube.com/embed/public'], $settings['allowed_patterns'] ?? null);
    }

    public function testItImportsTheCookies(): void
    {
        ComplianzFixture::install(self::OPTIONS, self::BANNER);
        \update_option('complianz_options_custom-scripts', [
            'block_script' => [
                ['enable' => 1, 'name' => 'Chat', 'urls' => ['chat.example.com'], 'category' => 'preferences'],
            ],
        ]);
        $chat = ComplianzFixture::addService('Chat');
        $other = ComplianzFixture::addService('Other service');
        $this->addCookie('wp_lang', 'Session', 0, 'Language', 'Functional');
        $this->addCookie('chat_id', '1 year', $chat, 'Chat session', 'Preferences');
        $this->addCookie('other', '1 day', $other, '', 'Marketing/Tracking');
        ComplianzFixture::addCookie(['name' => 'deleted', 'serviceID' => 0, 'deleted' => 1]);

        $imported = $this->sut()->read();
        $settings = $imported->settings();

        $this->assertSame(
            [['name' => 'wp_lang', 'category' => 'necessary', 'duration' => 'Session', 'purpose' => 'Language']],
            $settings['site_cookies'] ?? null
        );
        $vendors = $settings['custom_vendors'] ?? null;
        $this->assertIsArray($vendors);
        $this->assertSame(
            [['name' => 'chat_id', 'duration' => '1 year', 'purpose' => 'Chat session']],
            \array_column($vendors, 'cookies')[0] ?? null
        );
        $this->assertStringContainsString('Other service', \implode(' ', $imported->notes()));
    }

    public function testItNotesWhatIsNotImported(): void
    {
        ComplianzFixture::install(self::OPTIONS, self::BANNER);

        $notes = \implode("\n", $this->sut()->read()->notes());

        $this->assertStringContainsString('scroll', $notes);
        $this->assertStringContainsString('regions', $notes);
        $this->assertStringContainsString('CSS', $notes);
        $this->assertStringContainsString('Deactivate Complianz', $notes);
    }

    public function testItReadsTheSettingsOfTheVersionsBefore7(): void
    {
        \update_option('complianz_options_wizard', ['cookie_expiry' => 30]);
        \update_option('complianz_options_settings', ['respect_dnt' => 'yes']);

        $settings = $this->sut()->read()->settings();

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('30', $settings['consent_duration'] ?? null);
        $this->assertSame('1', $settings['global_privacy_control'] ?? null);
    }

    private function addCookie(string $name, string $retention, int $service, string $function, string $purpose): void
    {
        ComplianzFixture::addCookie([
            'name' => $name,
            'retention' => $retention,
            'serviceID' => $service,
            'cookieFunction' => $function,
            'purpose' => $purpose,
        ]);
    }

    private function sut(): ComplianzSource
    {
        return $this->service(ComplianzSource::class);
    }
}
