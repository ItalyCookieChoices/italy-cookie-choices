<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Infrastructure\CookieYes;

use ItalyCookieChoices\Migration\Infrastructure\CookieYes\CookieYesSource;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Migration\CookieYesFixture;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieYesSourceTest extends WPTestCase
{
    use BuildsContainer;

    private const SETTINGS = [
        'settings' => [
            'type' => 'box',
            'position' => 'bottom-right',
            'applicableLaw' => 'gdpr',
            'consentExpiry' => ['status' => true, 'value' => 100],
        ],
        'behaviours' => [
            'reloadBannerOnAccept' => ['status' => true],
            'respectGPC' => ['status' => true],
        ],
        'config' => [
            'notice' => [
                'styles' => ['background-color' => '#112233'],
                'elements' => [
                    'description' => ['styles' => ['color' => '#eeeeee']],
                    'brandLogo' => ['status' => false, 'meta' => ['url' => '#']],
                    'buttons' => ['elements' => [
                        'readMore' => ['status' => true, 'meta' => ['noFollow' => true, 'newTab' => true]],
                    ]],
                ],
            ],
            'revisitConsent' => ['status' => true, 'position' => 'bottom-left'],
        ],
        'meta' => ['customCSS' => '.cky-consent-container { color: red; }'],
    ];

    private const CONTENTS = [
        'en' => [
            'notice' => ['elements' => [
                'title' => 'We value your privacy',
                'description' => '<p>We use cookies.</p>',
                'privacyLink' => 'https://example.com/cookie-policy/',
                'buttons' => ['elements' => [
                    'accept' => 'Accept All',
                    'reject' => 'Reject All',
                    'settings' => 'Customize',
                    'readMore' => 'Cookie Policy',
                ]],
            ]],
            'preferenceCenter' => ['elements' => ['buttons' => ['elements' => ['save' => 'Save My Preferences']]]],
            'revisitConsent' => ['elements' => ['title' => 'Consent Preferences']],
            'videoPlaceholder' => ['elements' => ['title' => 'Accept the cookies to watch this video']],
        ],
        'it' => ['notice' => ['elements' => ['description' => '<p>Usiamo i cookie.</p>']]],
    ];

    public function testItIsFoundOnlyWithTheSettingsOfCookieYes(): void
    {
        $this->assertFalse($this->sut()->isFound());

        CookieYesFixture::install(self::SETTINGS, self::CONTENTS);

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('cookieyes', $this->sut()->id());
        $this->assertSame('CookieYes', $this->sut()->name());
    }

    public function testItImportsTheActiveBannerInTheDefaultLanguage(): void
    {
        CookieYesFixture::install(self::SETTINGS, self::CONTENTS);

        $settings = $this->sut()->read()->settings();

        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('<p>We use cookies.</p>', $settings['text'] ?? null);
        $this->assertSame('Accept All', $settings['button_text'] ?? null);
        $this->assertSame('Reject All', $settings['reject_button_text'] ?? null);
        $this->assertSame('Customize', $settings['preferences_button_text'] ?? null);
        $this->assertSame('Save My Preferences', $settings['save_button_text'] ?? null);
        $this->assertSame('https://example.com/cookie-policy/', $settings['url'] ?? null);
        $this->assertSame('Cookie Policy', $settings['anchor_text'] ?? null);
        $this->assertSame('1', $settings['target'] ?? null);
        $this->assertSame('1', $settings['nofollow'] ?? null);
        $this->assertSame('5', $settings['banner'] ?? null);
        $this->assertSame('#112233', $settings['banner_bg'] ?? null);
        $this->assertSame('#eeeeee', $settings['banner_text_color'] ?? null);
        $this->assertSame('90', $settings['consent_duration'] ?? null);
        $this->assertSame('1', $settings['reload'] ?? null);
        $this->assertSame('1', $settings['global_privacy_control'] ?? null);
        $this->assertSame('1', $settings['floating_button'] ?? null);
        $this->assertSame('left', $settings['floating_button_position'] ?? null);
        $this->assertSame('Consent Preferences', $settings['floating_button_label'] ?? null);
        $this->assertSame('Accept the cookies to watch this video', $settings['content_message_text'] ?? null);
        $this->assertStringStartsWith('/*', \is_string($settings['customCSS'] ?? null) ? $settings['customCSS'] : '');
    }

    public function testThePolicyLinkPointsToItsPage(): void
    {
        $policy = self::factory()->post->create([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'cookie-policy',
            'post_title' => 'Cookie policy',
        ]);
        $this->assertIsInt($policy);
        $contents = self::CONTENTS;
        $contents['en']['notice']['elements']['privacyLink'] = (string) \get_permalink($policy);
        CookieYesFixture::install(self::SETTINGS, $contents);

        $settings = $this->sut()->read()->settings();

        $this->assertSame('cookie-policy', $settings['slug'] ?? null);
        $this->assertSame('Cookie Policy', $settings['anchor_text'] ?? null);
    }

    public function testItImportsTheCategoriesAndTheCookies(): void
    {
        CookieYesFixture::install(self::SETTINGS, self::CONTENTS);
        $necessary = CookieYesFixture::addCategory('necessary', ['en' => 'Necessary'], ['en' => 'Always on.']);
        $analytics = CookieYesFixture::addCategory('analytics', ['en' => 'Analytics'], ['en' => 'Visits.']);
        $ads = CookieYesFixture::addCategory('advertisement', ['en' => 'Advertisement'], ['en' => 'Ads.']);
        CookieYesFixture::addCookie('wp_lang', $necessary, 'session', 'Language');
        CookieYesFixture::addCookie('cookieyes-consent', $necessary, '1 year', 'The consent');
        CookieYesFixture::addCookie('_ga', $analytics, '1 year 1 month', 'Visits', 'googletagmanager.com/gtag/js');
        CookieYesFixture::addCookie('_trk', $ads, '1 day', 'Ads', 'tracker.example.com/t.js');
        CookieYesFixture::addCookie('_trk_id', $ads, '1 day', 'Ads id', 'tracker.example.com/t.js');

        $imported = $this->sut()->read();
        $settings = $imported->settings();

        $this->assertSame(
            [
                'necessary' => ['label' => 'Necessary', 'description' => 'Always on.'],
                'statistics' => ['label' => 'Analytics', 'description' => 'Visits.'],
                'marketing' => ['label' => 'Advertisement', 'description' => 'Ads.'],
            ],
            $settings['categories'] ?? null
        );
        $this->assertSame(
            [['name' => 'wp_lang', 'category' => 'necessary', 'duration' => 'session', 'purpose' => 'Language']],
            $settings['site_cookies'] ?? null
        );
        $this->assertSame(
            [[
                'id' => 'tracker-example-com',
                'name' => 'tracker.example.com',
                'category' => 'marketing',
                'patterns' => ['tracker.example.com/t.js'],
                'cookies' => [
                    ['name' => '_trk', 'duration' => '1 day', 'purpose' => 'Ads'],
                    ['name' => '_trk_id', 'duration' => '1 day', 'purpose' => 'Ads id'],
                ],
            ]],
            $settings['custom_vendors'] ?? null
        );
        $this->assertSame(['google-analytics'], $imported->vendorsInUse());
    }

    public function testItImportsGoogleConsentMode(): void
    {
        CookieYesFixture::install(self::SETTINGS, self::CONTENTS);
        \update_option('cky_gcm_settings', ['status' => true]);

        $this->assertSame('1', $this->sut()->read()->settings()['consent_mode'] ?? null);
    }

    public function testItNotesWhatIsNotImported(): void
    {
        $settings = self::SETTINGS;
        $settings['settings']['applicableLaw'] = 'ccpa';
        CookieYesFixture::install($settings, self::CONTENTS);
        \update_option('cky_settings', ['account' => ['connected' => true], 'languages' => ['default' => 'en']]);

        $notes = \implode("\n", $this->sut()->read()->notes());

        $this->assertStringContainsString('web app', $notes);
        $this->assertStringContainsString('United States', $notes);
        $this->assertStringContainsString('CSS', $notes);
        $this->assertStringContainsString('Deactivate CookieYes', $notes);
    }

    public function testItImportsTheSettingsOfTheVersions2(): void
    {
        \update_option('CookieLawInfo-0.9', [
            'is_on' => true,
            'notify_message' => 'We use cookies. [cookie_settings] [cookie_accept_all]',
            'button_7_text' => 'Accept All',
            'button_3_text' => 'Reject All',
            'button_4_text' => 'Cookie Settings',
            'button_2_text' => 'Read More',
            'button_2_url_type' => 'url',
            'button_2_url' => 'https://example.com/privacy/',
            'button_2_new_win' => true,
            'background' => '#FFF',
            'text' => '#333333',
            'cookie_bar_as' => 'widget',
            'widget_position' => 'left',
            'showagain_tab' => true,
            'showagain_text' => 'Manage consent',
            'notify_position_horizontal' => 'right',
            'scroll_close' => true,
        ]);
        $cookie = self::factory()->post->create([
            'post_type' => 'cookielawinfo',
            'post_status' => 'publish',
            'post_title' => '_legacy',
            'post_content' => 'Remembers the visitor.',
        ]);
        $this->assertIsInt($cookie);
        \update_post_meta($cookie, '_cli_cookie_duration', '1 year');

        $imported = $this->sut()->read();
        $settings = $imported->settings();

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('We use cookies.', $settings['text'] ?? null);
        $this->assertSame('Accept All', $settings['button_text'] ?? null);
        $this->assertSame('Reject All', $settings['reject_button_text'] ?? null);
        $this->assertSame('Cookie Settings', $settings['preferences_button_text'] ?? null);
        $this->assertSame('https://example.com/privacy/', $settings['url'] ?? null);
        $this->assertSame('Read More', $settings['anchor_text'] ?? null);
        $this->assertSame('1', $settings['target'] ?? null);
        $this->assertSame('4', $settings['banner'] ?? null);
        $this->assertSame('#FFF', $settings['banner_bg'] ?? null);
        $this->assertSame('#333333', $settings['banner_text_color'] ?? null);
        $this->assertSame('right', $settings['floating_button_position'] ?? null);
        $this->assertSame(
            [[
                'name' => '_legacy',
                'category' => 'necessary',
                'duration' => '1 year',
                'purpose' => 'Remembers the visitor.',
            ]],
            $settings['site_cookies'] ?? null
        );
        $this->assertStringContainsString('scroll', \implode("\n", $imported->notes()));
    }

    private function sut(): CookieYesSource
    {
        return $this->service(CookieYesSource::class);
    }
}
