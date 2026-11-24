<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Infrastructure\CookieAdmin;

use ItalyCookieChoices\Migration\Application\MigrationSources;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Migration\Infrastructure\CookieAdmin\CookieAdminSource;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Migration\CookieAdminFixture;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieAdminSourceTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        \update_option('WPLANG', '');
    }

    public function tearDown(): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cookieadmin_cookies");
        parent::tearDown();
    }

    public function testItIsFoundOnlyWithTheSettingsOfCookieAdmin(): void
    {
        $this->assertFalse($this->sut()->isFound());

        CookieAdminFixture::install();

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('cookieadmin', $this->sut()->id());
        $this->assertSame('CookieAdmin', $this->sut()->name());
        $this->assertContains(
            'cookieadmin',
            \array_map(
                static fn(MigrationSource $source): string => $source->id(),
                $this->service(MigrationSources::class)->found()
            )
        );
    }

    public function testItImportsTheBanner(): void
    {
        CookieAdminFixture::install();
        $privacy = self::factory()->post->create([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'privacy',
            'post_title' => 'Privacy',
        ]);
        $this->assertIsInt($privacy);
        CookieAdminFixture::banner([
            'cookieadmin_layout' => 'box',
            'cookieadmin_position' => 'bottom_right',
            'cookieadmin_notice' => 'We use <b>cookies</b>.<script>alert(1)</script>',
            'cookieadmin_accept_btn' => 'Accept',
            'cookieadmin_reject_btn' => 'Reject',
            'cookieadmin_customize_btn' => 'Customize',
            'cookieadmin_save_btn' => 'Save',
            'cookieadmin_consent_inside_bg_color' => '#123456',
            'cookieadmin_notice_color' => '#fafafa',
            'cookieadmin_privacy_policy' => (string) $privacy,
            'cookieadmin_days' => '180',
        ]);
        CookieAdminFixture::settings([
            'google_consent_mode_v2' => 1,
            'clarity_consent' => 1,
            'respect_gpc' => 0,
            'content_blocking' => true,
            'content_blocking_services' => ['youtube', 'maps', 'unknown'],
        ]);

        $imported = $this->sut()->read();
        $settings = $imported->settings();

        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('We use <b>cookies</b>.alert(1)', $settings['text'] ?? null);
        $this->assertSame('Accept', $settings['button_text'] ?? null);
        $this->assertSame('Reject', $settings['reject_button_text'] ?? null);
        $this->assertSame('Customize', $settings['preferences_button_text'] ?? null);
        $this->assertSame('Save', $settings['save_button_text'] ?? null);
        $this->assertSame('privacy', $settings['slug'] ?? null);
        $this->assertSame(\get_permalink($privacy), $settings['url'] ?? null);
        $this->assertSame('Privacy', $settings['anchor_text'] ?? null);
        $this->assertSame('5', $settings['banner'] ?? null);
        $this->assertSame('#123456', $settings['banner_bg'] ?? null);
        $this->assertSame('#fafafa', $settings['banner_text_color'] ?? null);
        $this->assertSame('182', $settings['consent_duration'] ?? null);
        $this->assertSame('1', $settings['consent_mode'] ?? null);
        $this->assertSame('1', $settings['clarity_consent_mode'] ?? null);
        $this->assertArrayNotHasKey('global_privacy_control', $settings);
        $this->assertSame(['youtube', 'google-maps'], $imported->vendorsInUse());
    }

    public function testThePopUpAndTheFooterBar(): void
    {
        CookieAdminFixture::install();
        CookieAdminFixture::banner(['cookieadmin_layout' => 'popup']);
        $popup = $this->sut()->read()->settings();
        CookieAdminFixture::banner(['cookieadmin_layout' => 'footer', 'cookieadmin_position' => 'top']);
        $footer = $this->sut()->read()->settings();

        $this->assertSame('2', $popup['banner'] ?? null);
        $this->assertSame('1', $footer['banner'] ?? null);
    }

    public function testTheTextsLeftAtTheirDefaultsAreNotImported(): void
    {
        CookieAdminFixture::install();
        CookieAdminFixture::banner(['cookieadmin_layout' => 'box']);

        $settings = $this->sut()->read()->settings();

        $this->assertArrayNotHasKey('text', $settings);
        $this->assertArrayNotHasKey('button_text', $settings);
    }

    public function testTheScannedCookiesBecomeSiteCookiesOrVendors(): void
    {
        CookieAdminFixture::install();
        CookieAdminFixture::cookie('_ga_ABC123', 'Analytics', 'Google Analytics', 63072000);
        CookieAdminFixture::cookie('wp_lang', 'Functional', '<b>Language</b>', 3600);
        CookieAdminFixture::cookie('cart', 'Necessary', 'The cart', null, '2026-11-25 10:00:00', (int) \strtotime(
            '2026-11-24 10:00:00 UTC'
        ));
        CookieAdminFixture::cookie('session_id', 'Necessary', 'The session');
        CookieAdminFixture::cookie('mystery', 'Unknown', '');

        $imported = $this->sut()->read();

        $this->assertSame(
            [
                ['name' => 'wp_lang', 'category' => 'preferences', 'duration' => '60 minutes', 'purpose' => 'Language'],
                ['name' => 'cart', 'category' => 'necessary', 'duration' => '1 day', 'purpose' => 'The cart'],
                [
                    'name' => 'session_id',
                    'category' => 'necessary',
                    'duration' => 'Session',
                    'purpose' => 'The session',
                ],
            ],
            $imported->settings()['site_cookies'] ?? null
        );
        $this->assertSame(['google-analytics'], $imported->vendorsInUse());
        $this->assertStringContainsString('mystery', \implode("\n", $imported->notes()));
    }

    public function testASiteWithoutScansHasNoCookies(): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        CookieAdminFixture::install();
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cookieadmin_cookies");

        \ob_start();
        $settings = $this->sut()->read()->settings();

        $this->assertSame('', \ob_get_clean());
        $this->assertArrayNotHasKey('site_cookies', $settings);
    }

    private function sut(): CookieAdminSource
    {
        return $this->service(CookieAdminSource::class);
    }
}
