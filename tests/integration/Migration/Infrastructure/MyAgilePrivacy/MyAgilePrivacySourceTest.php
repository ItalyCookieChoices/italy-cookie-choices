<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Infrastructure\MyAgilePrivacy;

use ItalyCookieChoices\Migration\Application\MigrationSources;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Migration\Infrastructure\MyAgilePrivacy\MyAgilePrivacySource;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Migration\MyAgilePrivacyFixture;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class MyAgilePrivacySourceTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        \update_option('WPLANG', '');
    }

    public function testItIsFoundOnlyWithTheSettingsOfMyAgilePrivacy(): void
    {
        $this->assertFalse($this->sut()->isFound());

        MyAgilePrivacyFixture::settings(['is_on' => true]);

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('myagileprivacy', $this->sut()->id());
        $this->assertSame('My Agile Privacy', $this->sut()->name());
        $this->assertContains(
            'myagileprivacy',
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
        MyAgilePrivacyFixture::settings([
            'is_on' => true,
            'default_locale' => 'it_IT',
            'fixed_translations_encoded' => MyAgilePrivacyFixture::texts([
                'it_IT' => [
                    'notify_message_v2' => 'Usiamo i cookie.',
                    'accept' => 'Accetto',
                    'refuse' => 'Rifiuto',
                    'customize' => '',
                ],
                'en_US' => ['notify_message_v2' => 'We use cookies.'],
            ]),
            'cookie_policy_page' => $policy,
            'cookie_banner_vertical_position' => 'Bottom',
            'cookie_banner_horizontal_position' => 'Right',
            'cookie_banner_size' => 'sizeBoxed',
            'background' => '#04194e',
            'text' => '#ffffff',
            'showagain_tab' => true,
            'notify_position_horizontal' => 'left',
            'enable_cmode_v2' => true,
            'enable_microsoft_cmode' => false,
            'enable_clarity_cmode' => true,
            'enforce_youtube_privacy' => true,
            'custom_css' => '.map_notification-message { color: red; }',
        ]);

        $settings = $this->sut()->read()->settings();

        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('Usiamo i cookie.', $settings['text'] ?? null);
        $this->assertSame('Accetto', $settings['button_text'] ?? null);
        $this->assertSame('Rifiuto', $settings['reject_button_text'] ?? null);
        $this->assertArrayNotHasKey('preferences_button_text', $settings);
        $this->assertSame('cookie-policy', $settings['slug'] ?? null);
        $this->assertSame(\get_permalink($policy), $settings['url'] ?? null);
        $this->assertSame('5', $settings['banner'] ?? null);
        $this->assertSame('#04194e', $settings['banner_bg'] ?? null);
        $this->assertSame('#ffffff', $settings['banner_text_color'] ?? null);
        $this->assertSame('1', $settings['floating_button'] ?? null);
        $this->assertSame('left', $settings['floating_button_position'] ?? null);
        $this->assertSame('1', $settings['consent_mode'] ?? null);
        $this->assertArrayNotHasKey('uet_consent_mode', $settings);
        $this->assertSame('1', $settings['clarity_consent_mode'] ?? null);
        $this->assertSame('1', $settings['youtube_nocookie'] ?? null);
        $css = $settings['customCSS'] ?? null;
        $this->assertIsString($css);
        $this->assertStringContainsString('color: red', $css);
    }

    public function testThePolicyAddressAndThePositions(): void
    {
        MyAgilePrivacyFixture::settings([
            'is_on' => false,
            'is_cookie_policy_url' => true,
            'cookie_policy_url' => 'https://example.org/cookies',
            'cookie_banner_vertical_position' => 'Top',
            'cookie_banner_horizontal_position' => 'Left',
            'cookie_banner_size' => 'sizeWide',
        ]);
        $wide = $this->sut()->read()->settings();
        MyAgilePrivacyFixture::settings(['cookie_banner_vertical_position' => 'Center']);
        $center = $this->sut()->read()->settings();
        MyAgilePrivacyFixture::settings(['is_bottom' => false]);
        $legacy = $this->sut()->read()->settings();

        $this->assertArrayNotHasKey('active', $wide);
        $this->assertSame('https://example.org/cookies', $wide['url'] ?? null);
        $this->assertSame('1', $wide['banner'] ?? null);
        $this->assertSame('2', $center['banner'] ?? null);
        $this->assertSame('1', $legacy['banner'] ?? null);
    }

    public function testItImportsTheServices(): void
    {
        MyAgilePrivacyFixture::settings(['is_on' => true]);
        MyAgilePrivacyFixture::service('WordPress', 'wordpress', true);
        MyAgilePrivacyFixture::service('Google Analytics', 'google_analytics', false, [
            '_map_installation_type' => 'js_noscript',
            '_map_code' => "window.dataLayer = window.dataLayer || [];\ngtag('config', 'G-ABC1234');",
        ]);
        MyAgilePrivacyFixture::service('Facebook Remarketing', 'facebook_remarketing', false, [
            '_map_installation_type' => 'raw_code',
            '_map_raw_code' => "<script>fbq('init', '123456789');</script>",
        ]);
        MyAgilePrivacyFixture::service('YouTube', 'youtube');
        MyAgilePrivacyFixture::service('Google Maps', 'google_maps');
        MyAgilePrivacyFixture::service('Chat', 'chat_widget', false, [
            '_map_installation_type' => 'js_noscript',
            '_map_code' => 'loadChat();',
            '_map_noscript' => '<img src="https://chat.example/pixel.gif" alt="">',
        ]);
        MyAgilePrivacyFixture::service('Webinar', 'webinar_tool');
        MyAgilePrivacyFixture::service('Hotjar', 'hotjar', false, [], 'draft');

        $imported = $this->sut()->read();
        $settings = $imported->settings();
        $notes = \implode("\n", $imported->notes());

        $this->assertSame(['google-analytics', 'facebook', 'youtube', 'google-maps'], $imported->vendorsInUse());
        $this->assertSame('G-ABC1234', $settings['ga_measurement_id'] ?? null);
        $this->assertSame('123456789', $settings['facebook_pixel_id'] ?? null);
        $this->assertSame(
            [
                [
                    'name' => 'Chat',
                    'category' => 'marketing',
                    'position' => 'body',
                    'code' => "<script>\nloadChat();\n</script>\n"
                        . '<noscript><img src="https://chat.example/pixel.gif" alt=""></noscript>',
                ],
            ],
            $settings['custom_scripts'] ?? null
        );
        $this->assertStringContainsString('Chat, Webinar', $notes);
        $this->assertStringContainsString('WordPress', $notes);
        $this->assertStringNotContainsString('Hotjar', $notes);
    }

    private function sut(): MyAgilePrivacySource
    {
        return $this->service(MyAgilePrivacySource::class);
    }
}
