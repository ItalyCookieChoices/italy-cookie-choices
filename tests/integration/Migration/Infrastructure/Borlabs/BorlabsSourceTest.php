<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Infrastructure\Borlabs;

use ItalyCookieChoices\Migration\Infrastructure\Borlabs\BorlabsSource;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Migration\BorlabsFixture;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class BorlabsSourceTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        \update_option('WPLANG', '');
    }

    public function testItIsFoundOnlyWithTheSettingsOfBorlabsCookie(): void
    {
        $this->assertFalse($this->sut()->isFound());

        BorlabsFixture::install();

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('borlabs-cookie', $this->sut()->id());
        $this->assertSame('Borlabs Cookie', $this->sut()->name());
    }

    public function testItImportsTheDialogWithoutCreatingItsObjects(): void
    {
        BorlabsFixture::install();
        $this->configure();

        $settings = $this->sut()->read()->settings();

        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('We use cookies.', $settings['text'] ?? null);
        $this->assertSame('Accept all', $settings['button_text'] ?? null);
        $this->assertSame('Only essential', $settings['reject_button_text'] ?? null);
        $this->assertSame('Preferences', $settings['preferences_button_text'] ?? null);
        $this->assertSame('Save', $settings['save_button_text'] ?? null);
        $this->assertSame('https://example.com/privacy/', $settings['url'] ?? null);
        $this->assertSame('Privacy', $settings['anchor_text'] ?? null);
        $this->assertSame('https://example.com/imprint/', $settings['legal_notice_url'] ?? null);
        $this->assertSame('Imprint', $settings['legal_notice_text'] ?? null);
        $this->assertSame('5', $settings['banner'] ?? null);
        $this->assertSame('#123456', $settings['banner_bg'] ?? null);
        $this->assertSame('#fafafa', $settings['banner_text_color'] ?? null);
        $this->assertSame('30', $settings['consent_duration'] ?? null);
        $this->assertSame('example.com', $settings['cookie_domain'] ?? null);
        $this->assertSame('1', $settings['reload'] ?? null);
        $this->assertSame('1', $settings['floating_button'] ?? null);
        $this->assertSame('left', $settings['floating_button_position'] ?? null);
        $this->assertSame(['youtube.com/embed/public'], $settings['allowed_patterns'] ?? null);
    }

    public function testItImportsTheServices(): void
    {
        BorlabsFixture::install();
        $this->configure();
        $essential = BorlabsFixture::group('essential', 'Essential', 'Needed.');
        $statistics = BorlabsFixture::group('statistics', 'Statistics', 'Visits.');
        $marketing = BorlabsFixture::group('marketing', 'Marketing', 'Ads.');
        $site = BorlabsFixture::service('borlabs-cookie', 'Borlabs Cookie', $essential);
        BorlabsFixture::cookie($site, 'borlabs-cookie', '60 days', 'The consent');
        BorlabsFixture::service('google-analytics', 'Google Analytics', $statistics, [
            'google-analytics-tracking-id' => 'G-ICC123',
            'google-analytics-consent-mode' => '1',
        ]);
        $chat = BorlabsFixture::service('chat', 'Chat', $marketing, [], '<script>chat();</script>');
        BorlabsFixture::location($chat, 'chat.example.com', '/widget.js');
        BorlabsFixture::cookie($chat, 'chat_id', '1 year', 'The chat');
        BorlabsFixture::service('mystery', 'Mystery', $marketing, [], '<script>{{ id }}</script>');

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
            [[
                'name' => 'borlabs-cookie',
                'category' => 'necessary',
                'duration' => '60 days',
                'purpose' => 'The consent',
            ]],
            $settings['site_cookies'] ?? null
        );
        $this->assertSame(
            [[
                'id' => 'chat',
                'name' => 'Chat',
                'category' => 'marketing',
                'patterns' => ['chat.example.com/widget.js'],
                'cookies' => [['name' => 'chat_id', 'duration' => '1 year', 'purpose' => 'The chat']],
            ]],
            $settings['custom_vendors'] ?? null
        );
        $this->assertSame(
            [['name' => 'Chat', 'category' => 'marketing', 'position' => 'body', 'code' => '<script>chat();</script>']],
            $settings['custom_scripts'] ?? null
        );
        $this->assertSame('G-ICC123', $settings['ga_measurement_id'] ?? null);
        $this->assertSame('1', $settings['consent_mode'] ?? null);
        $this->assertSame(['google-analytics'], $imported->vendorsInUse());
        $this->assertStringContainsString('Mystery', \implode("\n", $imported->notes()));
    }

    public function testADisabledDialogIsNotTurnedOn(): void
    {
        BorlabsFixture::install();
        BorlabsFixture::config('GeneralConfig', ['borlabsCookieStatus' => false]);

        $this->assertArrayNotHasKey('active', $this->sut()->read()->settings());
    }

    private function configure(): void
    {
        BorlabsFixture::config('GeneralConfig', [
            'borlabsCookieStatus' => true,
            'cookieLifetime' => 60,
            'automaticCookieDomainAndPath' => false,
            'cookieDomain' => 'example.com',
            'reloadAfterOptIn' => true,
        ]);
        BorlabsFixture::config('DialogSettingsConfig', [
            'showDialog' => true,
            'layout' => 'box',
            'position' => 'bottom-right',
            'privacyPageId' => 0,
            'privacyPageCustomUrl' => 'https://example.com/privacy/',
            'imprintPageId' => 0,
            'imprintPageCustomUrl' => 'https://example.com/imprint/',
            'hideDialogOnPages' => [],
            'showLogo' => false,
            'logo' => '',
        ]);
        BorlabsFixture::config('DialogStyleConfig', [
            'dialogBackgroundColor' => '#123456',
            'dialogTextColor' => '#fafafa',
            'customCss' => '',
        ]);
        BorlabsFixture::config('DialogLocalization', [
            'entranceDescription' => 'We use cookies.',
            'entranceAcceptAllButton' => 'Accept all',
            'entranceAcceptOnlyEssential' => 'Only essential',
            'entrancePreferencesButton' => 'Preferences',
            'entranceSaveConsentButton' => 'Save',
            'privacyLink' => 'Privacy',
            'imprintLink' => 'Imprint',
        ]);
        BorlabsFixture::config('WidgetConfig', ['show' => true, 'position' => 'bottom-left']);
        BorlabsFixture::config('ContentBlockerConfig', ['excludedHostnames' => ['youtube.com/embed/public']]);
    }

    private function sut(): BorlabsSource
    {
        return $this->service(BorlabsSource::class);
    }
}
