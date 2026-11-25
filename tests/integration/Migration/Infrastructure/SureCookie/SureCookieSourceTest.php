<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Infrastructure\SureCookie;

use ItalyCookieChoices\Migration\Application\CookieLifetime;
use ItalyCookieChoices\Migration\Application\MigrationSources;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Migration\Infrastructure\SureCookie\SureCookieSource;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Migration\SureCookieFixture;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SureCookieSourceTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        \update_option('WPLANG', '');
    }

    public function testItIsFoundOnlyWithTheSettingsOfSureCookie(): void
    {
        $this->assertFalse($this->sut()->isFound());

        SureCookieFixture::settings(['banner_enabled' => true]);

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('surecookie', $this->sut()->id());
        $this->assertSame('SureCookie', $this->sut()->name());
        $this->assertContains(
            'surecookie',
            \array_map(
                static fn(MigrationSource $source): string => $source->id(),
                $this->service(MigrationSources::class)->found()
            )
        );
    }

    public function testItImportsTheBanner(): void
    {
        $policy = $this->page('cookie-policy', 'Cookie policy');
        $notice = $this->page('legal-notice', 'Legal notice');
        SureCookieFixture::settings([
            'banner_enabled' => true,
            'message_description' => 'We use <strong>cookies</strong>.',
            'accept_btn_text' => 'Only essential',
            'accept_all_btn_text' => 'Accept all',
            'decline_btn_text' => 'Decline',
            'preferences_btn_text' => 'Preferences',
            'cookie_policy_page_id' => $policy,
            'legal_notice_page_id' => $notice,
            'notice_type' => 'box',
            'notice_position' => 'bottom-left',
            'consent_duration_days' => 365,
            'gcm_enabled' => true,
            'floating_widget_enabled' => true,
            'custom_css' => '.surecookie-banner { color: red; }',
        ]);

        $settings = $this->sut()->read()->settings();

        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('We use <strong>cookies</strong>.', $settings['text'] ?? null);
        $this->assertSame('Accept all', $settings['button_text'] ?? null);
        $this->assertSame('Decline', $settings['reject_button_text'] ?? null);
        $this->assertSame('Preferences', $settings['preferences_button_text'] ?? null);
        $this->assertSame('cookie-policy', $settings['slug'] ?? null);
        $this->assertSame(\get_permalink($policy), $settings['url'] ?? null);
        $this->assertSame('Cookie policy', $settings['anchor_text'] ?? null);
        $this->assertSame('legal-notice', $settings['legal_notice_page'] ?? null);
        $this->assertSame('4', $settings['banner'] ?? null);
        $this->assertSame('365', $settings['consent_duration'] ?? null);
        $this->assertSame('1', $settings['consent_mode'] ?? null);
        $this->assertSame('1', $settings['floating_button'] ?? null);
        $css = $settings['customCSS'] ?? null;
        $this->assertIsString($css);
        $this->assertStringContainsString('color: red', $css);
    }

    public function testTheFullWidthBanners(): void
    {
        SureCookieFixture::settings(['banner_enabled' => false, 'notice_type' => 'banner', 'notice_position' => 'top']);
        $top = $this->sut()->read()->settings();
        SureCookieFixture::settings(['notice_type' => 'banner', 'notice_position' => 'bottom']);
        $bottom = $this->sut()->read()->settings();

        $this->assertArrayNotHasKey('active', $top);
        $this->assertSame('1', $top['banner'] ?? null);
        $this->assertSame('1', $bottom['active'] ?? null);
        $this->assertSame('3', $bottom['banner'] ?? null);
    }

    public function testItImportsTheCategoriesTheServicesAndTheCookies(): void
    {
        SureCookieFixture::settings([
            'cookie_categories' => [
                'essential' => ['id' => 'essential', 'name' => 'Needed', 'description' => 'For the site.'],
                'analytics' => ['id' => 'analytics', 'name' => 'Statistics', 'description' => '<p>Visits.</p>'],
                'uncategorized' => ['id' => 'uncategorized', 'name' => 'Others', 'description' => 'Unknown.'],
            ],
            'custom_cookies' => [
                'c1' => SureCookieFixture::cookie('c1', '_ga', 'analytics', '730', 'Google Analytics'),
                'c2' => SureCookieFixture::cookie('c2', 'wp_lang', 'essential', '0', 'The language'),
                'c3' => SureCookieFixture::cookie('c3', 'intercom-id', 'functional', '270', '<b>The chat</b>'),
            ],
            'custom_blocked_scripts' => [
                ['value' => 'widget.example.com', 'category' => 'functional'],
                ['value' => 'connect.facebook.net', 'category' => 'marketing'],
            ],
        ]);
        SureCookieFixture::services(['google-analytics' => ['c1'], 'youtube' => [], 'intercom' => ['c3']]);
        SureCookieFixture::scannedCookies([
            'essential' => [['name' => 'wp_lang', 'duration' => '1']],
            'marketing' => [['name' => '_fbp', 'duration' => '90']],
            'uncategorized' => [
                ['name' => 'mystery', 'duration' => '', 'expiry_bucket' => 'session', 'description' => 'Who knows'],
            ],
        ]);
        $lifetime = $this->service(CookieLifetime::class);

        $imported = $this->sut()->read();
        $settings = $imported->settings();

        $this->assertSame(
            [
                'necessary' => ['label' => 'Needed', 'description' => 'For the site.'],
                'statistics' => ['label' => 'Statistics', 'description' => 'Visits.'],
            ],
            $settings['categories'] ?? null
        );
        $this->assertSame(['google-analytics', 'youtube', 'facebook'], $imported->vendorsInUse());
        $this->assertSame(
            [
                [
                    'name' => 'wp_lang',
                    'category' => 'necessary',
                    'duration' => $lifetime->text(null),
                    'purpose' => 'The language',
                ],
                [
                    'name' => 'intercom-id',
                    'category' => 'preferences',
                    'duration' => $lifetime->text(270 * CookieLifetime::DAY),
                    'purpose' => 'The chat',
                ],
                [
                    'name' => 'mystery',
                    'category' => 'marketing',
                    'duration' => $lifetime->text(null),
                    'purpose' => 'Who knows',
                ],
            ],
            $settings['site_cookies'] ?? null
        );
        $this->assertSame(
            [
                [
                    'id' => 'widget-example-com',
                    'name' => 'widget.example.com',
                    'category' => 'preferences',
                    'patterns' => ['widget.example.com'],
                ],
            ],
            $settings['custom_vendors'] ?? null
        );
        $this->assertStringContainsString('Intercom', \implode("\n", $imported->notes()));
    }

    private function page(string $slug, string $title): int
    {
        $page = self::factory()->post->create([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => $slug,
            'post_title' => $title,
        ]);
        $this->assertIsInt($page);

        return $page;
    }

    private function sut(): SureCookieSource
    {
        return $this->service(SureCookieSource::class);
    }
}
