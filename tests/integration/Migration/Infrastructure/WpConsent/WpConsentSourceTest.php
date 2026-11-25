<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Infrastructure\WpConsent;

use ItalyCookieChoices\Migration\Application\MigrationSources;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Migration\Infrastructure\WpConsent\WpConsentSource;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Migration\WpConsentFixture;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class WpConsentSourceTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        \update_option('WPLANG', '');
    }

    public function testItIsFoundOnlyWithTheSettingsOfWpConsent(): void
    {
        $this->assertFalse($this->sut()->isFound());

        WpConsentFixture::settings(['enable_consent_banner' => 1]);

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('wpconsent', $this->sut()->id());
        $this->assertSame('WPConsent', $this->sut()->name());
        $this->assertContains(
            'wpconsent',
            \array_map(
                static fn(MigrationSource $source): string => $source->id(),
                $this->service(MigrationSources::class)->found()
            )
        );
    }

    public function testItImportsTheBanner(): void
    {
        $policy = self::factory()->post->create([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'cookie-policy',
            'post_title' => 'Cookie policy',
        ]);
        $this->assertIsInt($policy);
        WpConsentFixture::settings([
            'enable_consent_banner' => 1,
            'banner_message' => 'We use <strong>cookies</strong>.',
            'accept_button_text' => 'Accept',
            'cancel_button_text' => 'Reject',
            'preferences_button_text' => 'Preferences',
            'save_preferences_button_text' => 'Save',
            'cookie_policy_page' => $policy,
            'banner_layout' => 'floating',
            'banner_position' => 'right-bottom',
            'banner_background_color' => '#04194e',
            'banner_text_color' => '#ffffff',
            'consent_duration' => 365,
            'google_consent_mode' => 1,
            'respect_gpc' => 0,
        ]);

        $settings = $this->sut()->read()->settings();

        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('We use <strong>cookies</strong>.', $settings['text'] ?? null);
        $this->assertSame('Accept', $settings['button_text'] ?? null);
        $this->assertSame('Reject', $settings['reject_button_text'] ?? null);
        $this->assertSame('Preferences', $settings['preferences_button_text'] ?? null);
        $this->assertSame('Save', $settings['save_button_text'] ?? null);
        $this->assertSame('cookie-policy', $settings['slug'] ?? null);
        $this->assertSame(\get_permalink($policy), $settings['url'] ?? null);
        $this->assertSame('Cookie policy', $settings['anchor_text'] ?? null);
        $this->assertSame('5', $settings['banner'] ?? null);
        $this->assertSame('#04194e', $settings['banner_bg'] ?? null);
        $this->assertSame('#ffffff', $settings['banner_text_color'] ?? null);
        $this->assertSame('365', $settings['consent_duration'] ?? null);
        $this->assertSame('1', $settings['consent_mode'] ?? null);
        $this->assertArrayNotHasKey('global_privacy_control', $settings);
    }

    public function testTheLongBannerAndTheModal(): void
    {
        WpConsentFixture::settings(['banner_layout' => 'long', 'banner_position' => 'top']);
        $long = $this->sut()->read()->settings();
        WpConsentFixture::settings(['banner_layout' => 'modal']);
        $modal = $this->sut()->read()->settings();

        $this->assertSame('1', $long['banner'] ?? null);
        $this->assertArrayNotHasKey('active', $long);
        $this->assertSame('2', $modal['banner'] ?? null);
    }

    public function testItImportsTheCategoriesTheServicesAndTheCookies(): void
    {
        WpConsentFixture::settings(['enable_consent_banner' => 1]);
        $essential = WpConsentFixture::category('essential', 'Essential', 'Needed.');
        $statistics = WpConsentFixture::category('statistics', 'Statistics', '<p>Visits.</p>');
        $marketing = WpConsentFixture::category('marketing', 'Marketing', 'Ads.');
        WpConsentFixture::cookie($essential, 'wpconsent_preferences', '30 days', 'The consent');
        WpConsentFixture::cookie($essential, 'wp_lang', 'Session', 'The language');
        $analytics = WpConsentFixture::service($statistics, 'Google Analytics', 'google-analytics');
        WpConsentFixture::cookie($analytics, '_ga', '2 years', 'Google Analytics');
        $pixel = WpConsentFixture::service($marketing, 'Facebook Pixel', 'facebook-pixel');
        WpConsentFixture::cookie($pixel, '_fbp', '3 months', 'Facebook');
        $chat = WpConsentFixture::service($marketing, 'Chat Widget');
        WpConsentFixture::cookie($chat, 'chat_id', '1 year', '<b>The chat</b>');

        $imported = $this->sut()->read();
        $settings = $imported->settings();

        $this->assertSame(
            [
                'necessary' => ['label' => 'Essential', 'description' => 'Needed.'],
                'statistics' => ['label' => 'Statistics', 'description' => 'Visits.'],
                'marketing' => ['label' => 'Marketing', 'description' => 'Ads.'],
            ],
            $settings['categories'] ?? null
        );
        $this->assertSame(
            [
                ['name' => 'wp_lang', 'category' => 'necessary', 'duration' => 'Session', 'purpose' => 'The language'],
                ['name' => 'chat_id', 'category' => 'marketing', 'duration' => '1 year', 'purpose' => 'The chat'],
            ],
            $settings['site_cookies'] ?? null
        );
        $this->assertSame(['google-analytics', 'facebook'], $imported->vendorsInUse());
        $this->assertStringContainsString('Chat Widget', \implode("\n", $imported->notes()));
    }

    private function sut(): WpConsentSource
    {
        return $this->service(WpConsentSource::class);
    }
}
