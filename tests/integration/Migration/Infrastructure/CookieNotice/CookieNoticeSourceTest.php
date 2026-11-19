<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Infrastructure\CookieNotice;

use ItalyCookieChoices\Migration\Infrastructure\CookieNotice\CookieNoticeSource;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieNoticeSourceTest extends WPTestCase
{
    use BuildsContainer;

    private const OPTIONS = [
        'position' => 'top',
        'message_text' => 'We use cookies.',
        'accept_text' => 'Ok',
        'refuse_text' => 'No',
        'refuse_opt' => true,
        'refuse_code' => '<script>track();</script>',
        'refuse_code_head' => '<script src="https://tracker.example.com/t.js"></script>',
        'revoke_cookies' => true,
        'revoke_cookies_opt' => 'automatic',
        'revoke_text' => 'Revoke cookies',
        'redirection' => true,
        'see_more' => true,
        'link_target' => '_blank',
        'time' => '6months',
        'on_scroll' => true,
        'conditional_active' => true,
        'colors' => ['text' => '#fff', 'button' => '#00a99d', 'bar' => '#32323a', 'bar_opacity' => 100],
        'see_more_opt' => [
            'text' => 'Privacy policy',
            'link_type' => 'custom',
            'id' => 0,
            'link' => 'https://example.com/privacy/',
        ],
    ];

    public function testItIsFoundOnlyWithTheSettingsOfCookieNotice(): void
    {
        $this->assertFalse($this->sut()->isFound());

        \update_option('cookie_notice_options', self::OPTIONS);

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('cookie-notice', $this->sut()->id());
        $this->assertSame('Cookie Notice & Compliance', $this->sut()->name());
    }

    public function testItImportsTheBannerOnly(): void
    {
        \update_option('cookie_notice_options', self::OPTIONS);

        $settings = $this->sut()->read()->settings();

        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('We use cookies.', $settings['text'] ?? null);
        $this->assertSame('Ok', $settings['button_text'] ?? null);
        $this->assertSame('No', $settings['reject_button_text'] ?? null);
        $this->assertSame('https://example.com/privacy/', $settings['url'] ?? null);
        $this->assertSame('Privacy policy', $settings['anchor_text'] ?? null);
        $this->assertSame('1', $settings['target'] ?? null);
        $this->assertSame('1', $settings['banner'] ?? null);
        $this->assertSame('#32323a', $settings['banner_bg'] ?? null);
        $this->assertSame('#fff', $settings['banner_text_color'] ?? null);
        $this->assertSame('182', $settings['consent_duration'] ?? null);
        $this->assertSame('1', $settings['reload'] ?? null);
        $this->assertSame('1', $settings['floating_button'] ?? null);
        $this->assertSame('Revoke cookies', $settings['floating_button_label'] ?? null);
        $this->assertSame(
            [
                [
                    'name' => 'Cookie Notice (head)',
                    'category' => 'marketing',
                    'position' => 'head',
                    'code' => '<script src="https://tracker.example.com/t.js"></script>',
                ],
                [
                    'name' => 'Cookie Notice',
                    'category' => 'marketing',
                    'position' => 'body',
                    'code' => '<script>track();</script>',
                ],
            ],
            $settings['custom_scripts'] ?? null
        );
    }

    public function testThePolicyPageIsLinked(): void
    {
        $page = self::factory()->post->create([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'privacy',
            'post_title' => 'Privacy',
        ]);
        $this->assertIsInt($page);
        \update_option('cookie_notice_options', [
            ...self::OPTIONS,
            'see_more_opt' => ['text' => '', 'link_type' => 'page', 'id' => $page, 'link' => ''],
        ]);

        $settings = $this->sut()->read()->settings();

        $this->assertSame('privacy', $settings['slug'] ?? null);
        $this->assertSame((string) \get_permalink($page), $settings['url'] ?? null);
        $this->assertSame('Privacy', $settings['anchor_text'] ?? null);
    }

    public function testItImportsWhatTheComplianceAppKeepsOnTheSite(): void
    {
        \update_option('cookie_notice_options', [...self::OPTIONS, 'app_id' => 'app', 'app_key' => 'key']);
        \update_option('cookie_notice_status', ['status' => 'active']);
        \update_option('cookie_notice_app_design', [
            'position' => 'right',
            'bannerColor' => '#ffffff',
            'privacyPolicyUrl' => 'https://example.com/app-privacy/',
        ]);
        \update_option('cookie_notice_app_blocking', [
            'gpc_support' => true,
            'google_consent_default' => ['ad_storage' => 'denied'],
            'providers' => [
                (object) ['ProviderID' => 7, 'ProviderName' => 'Chat', 'CategoryID' => 2, 'IsCustom' => true],
                (object) ['ProviderID' => 8, 'ProviderName' => 'YouTube', 'CategoryID' => 4, 'IsCustom' => false],
            ],
            'patterns' => [
                (object) ['ProviderID' => 7, 'Pattern' => 'chat.example.com', 'CategoryID' => 2, 'IsCustom' => true],
                (object) ['ProviderID' => 8, 'Pattern' => 'youtube.com', 'CategoryID' => 4, 'IsCustom' => false],
            ],
        ]);

        $imported = $this->sut()->read();
        $settings = $imported->settings();

        $this->assertArrayNotHasKey('text', $settings);
        $this->assertArrayNotHasKey('custom_scripts', $settings);
        $this->assertSame('5', $settings['banner'] ?? null);
        $this->assertSame('#ffffff', $settings['banner_bg'] ?? null);
        $this->assertSame('https://example.com/app-privacy/', $settings['url'] ?? null);
        $this->assertSame('1', $settings['global_privacy_control'] ?? null);
        $this->assertSame('1', $settings['consent_mode'] ?? null);
        $this->assertSame(
            [['id' => 'chat', 'name' => 'Chat', 'category' => 'preferences', 'patterns' => ['chat.example.com']]],
            $settings['custom_vendors'] ?? null
        );
        $this->assertStringContainsString('Compliance', \implode("\n", $imported->notes()));
    }

    public function testItNotesWhatIsNotImported(): void
    {
        \update_option('cookie_notice_options', self::OPTIONS);

        $notes = \implode("\n", $this->sut()->read()->notes());

        $this->assertStringContainsString('scroll', $notes);
        $this->assertStringContainsString('conditional', $notes);
        $this->assertStringContainsString('marketing', $notes);
        $this->assertStringContainsString('Deactivate Cookie Notice', $notes);
    }

    private function sut(): CookieNoticeSource
    {
        return $this->service(CookieNoticeSource::class);
    }
}
