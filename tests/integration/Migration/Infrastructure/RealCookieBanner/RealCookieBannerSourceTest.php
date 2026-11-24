<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Infrastructure\RealCookieBanner;

use ItalyCookieChoices\Migration\Application\MigrationSources;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Migration\Infrastructure\RealCookieBanner\RealCookieBannerSource;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Migration\RealCookieBannerFixture;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class RealCookieBannerSourceTest extends WPTestCase
{
    use BuildsContainer;

    private const HOTJAR = "<script>(function(h,o,t,j,a,r){r.src=t+h._hjSettings.hjid+j})(window,document,"
        . "'https://static.hotjar.com/c/hotjar-','.js?sv=');</script>";

    public function setUp(): void
    {
        parent::setUp();
        \update_option('WPLANG', '');
    }

    public function testItIsFoundOnlyWithTheSettingsOfRealCookieBanner(): void
    {
        $this->assertFalse($this->sut()->isFound());

        RealCookieBannerFixture::install();
        RealCookieBannerFixture::option('banner-active', '1');

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('real-cookie-banner', $this->sut()->id());
        $this->assertSame('Real Cookie Banner', $this->sut()->name());
        $this->assertContains(
            'real-cookie-banner',
            \array_map(
                static fn(MigrationSource $source): string => $source->id(),
                $this->service(MigrationSources::class)->found()
            )
        );
    }

    public function testItImportsTheBanner(): void
    {
        RealCookieBannerFixture::install();
        $this->configure();
        $privacy = self::factory()->post->create([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'privacy',
            'post_title' => 'Privacy',
        ]);
        $this->assertIsInt($privacy);
        RealCookieBannerFixture::link('Our privacy', [
            'pageType' => 'privacyPolicy',
            'isExternalUrl' => '',
            'pageId' => (string) $privacy,
            'hideCookieBanner' => '1',
        ]);
        RealCookieBannerFixture::link('Imprint', [
            'pageType' => 'legalNotice',
            'isExternalUrl' => '1',
            'externalUrl' => 'https://example.org/imprint/',
            'hideCookieBanner' => '',
        ]);

        $settings = $this->sut()->read()->settings();

        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('We use cookies, read the privacy policy.', $settings['text'] ?? null);
        $this->assertSame('Accept all', $settings['button_text'] ?? null);
        $this->assertSame('Only essentials', $settings['reject_button_text'] ?? null);
        $this->assertSame('Choose', $settings['preferences_button_text'] ?? null);
        $this->assertSame('Save', $settings['save_button_text'] ?? null);
        $this->assertSame('privacy', $settings['slug'] ?? null);
        $this->assertSame(\get_permalink($privacy), $settings['url'] ?? null);
        $this->assertSame('Our privacy', $settings['anchor_text'] ?? null);
        $this->assertSame('https://example.org/imprint/', $settings['legal_notice_url'] ?? null);
        $this->assertSame('Imprint', $settings['legal_notice_text'] ?? null);
        $this->assertSame('privacy', $settings['excluded_pages'] ?? null);
        $this->assertSame('5', $settings['banner'] ?? null);
        $this->assertSame('#123456', $settings['banner_bg'] ?? null);
        $this->assertSame('#fafafa', $settings['banner_text_color'] ?? null);
        $this->assertSame('90', $settings['consent_duration'] ?? null);
        $this->assertSame('1', $settings['floating_button'] ?? null);
        $this->assertSame('left', $settings['floating_button_position'] ?? null);
        $css = $settings['customCSS'] ?? null;
        $this->assertIsString($css);
        $this->assertStringContainsString('.rcb-banner { color: red; }', $css);
    }

    public function testTheTextsOfTheLanguageOfTheSiteWin(): void
    {
        RealCookieBannerFixture::install();
        RealCookieBannerFixture::option('banner-texts-accept-all', 'Accept all');
        RealCookieBannerFixture::option('banner-texts-accept-all-it', 'Accetta tutto');
        \add_filter('locale', static fn(): string => 'it_IT');

        $this->assertSame('Accetta tutto', $this->sut()->read()->settings()['button_text'] ?? null);
    }

    public function testABannerTurnedOffStaysOff(): void
    {
        RealCookieBannerFixture::install();
        RealCookieBannerFixture::option('banner-active', '');
        RealCookieBannerFixture::option('banner-layout-type', 'banner');
        RealCookieBannerFixture::option('banner-layout-banner-position', 'top');

        $settings = $this->sut()->read()->settings();

        $this->assertArrayNotHasKey('active', $settings);
        $this->assertSame('1', $settings['banner'] ?? null);
    }

    public function testItImportsTheServicesAndTheBlockers(): void
    {
        RealCookieBannerFixture::install();
        $essential = RealCookieBannerFixture::group('Essential', 'Needed.', true, 0);
        $statistics = RealCookieBannerFixture::group('Statistics', '<p>Visits.</p>', false, 2);
        $marketing = RealCookieBannerFixture::group('Marketing', 'Ads.', false, 3);
        RealCookieBannerFixture::service('Real Cookie Banner', $essential, ['presetId' => 'real-cookie-banner']);
        RealCookieBannerFixture::service('Login', $essential, ['technicalDefinitions' => [
            ['type' => 'http', 'name' => 'wordpress_logged_in_*', 'duration' => 0, 'isSessionDuration' => true],
            ['type' => 'local', 'name' => 'some-storage', 'duration' => 1, 'durationUnit' => 'y'],
        ]]);
        RealCookieBannerFixture::service('Google Analytics', $statistics, [
            'presetId' => 'google-analytics-analytics-4',
            'codeOptIn' => '<script>gtag("config", "{{measurementId}}");</script>',
            'codeDynamics' => ['measurementId' => 'G-ICC123'],
        ]);
        RealCookieBannerFixture::service('Hotjar', $statistics, ['presetId' => 'hotjar', 'codeOptIn' => self::HOTJAR]);
        $chat = RealCookieBannerFixture::service('Chat', $marketing, [
            'codeOptIn' => '<script>chat("{{chatId}}");</script>',
            'codeDynamics' => ['chatId' => '42'],
            'technicalDefinitions' => [
                [
                    'type' => 'http',
                    'name' => 'chat_id',
                    'duration' => 1,
                    'durationUnit' => 'y',
                    'purpose' => 'The chat',
                ],
            ],
        ]);
        RealCookieBannerFixture::blocker('Chat', ['*chat.example.com/widget.js*', 'div[class*="chat"]'], [$chat]);
        RealCookieBannerFixture::service('Mystery', $marketing, ['codeOptIn' => '<script>{{missing}}</script>']);
        RealCookieBannerFixture::service('Disabled', $marketing, ['codeOptIn' => '<script>off();</script>'], 'draft');

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
            [['name' => 'wordpress_logged_in_*', 'category' => 'necessary', 'duration' => 'Session', 'purpose' => '']],
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
            [
                ['name' => 'Hotjar', 'category' => 'statistics', 'position' => 'body', 'code' => self::HOTJAR],
                [
                    'name' => 'Chat',
                    'category' => 'marketing',
                    'position' => 'body',
                    'code' => '<script>chat("42");</script>',
                ],
            ],
            $settings['custom_scripts'] ?? null
        );
        $this->assertSame('G-ICC123', $settings['ga_measurement_id'] ?? null);
        $this->assertSame(['google-analytics', 'hotjar'], $imported->vendorsInUse());
        $notes = \implode("\n", $imported->notes());
        $this->assertStringContainsString('Mystery', $notes);
        $this->assertStringNotContainsString('Disabled', $notes);
    }

    private function configure(): void
    {
        $options = [
            'banner-active' => '1',
            'banner-texts-description' => 'We use cookies, read the {{privacyPolicy}}privacy policy{{/privacyPolicy}}.',
            'banner-texts-accept-all' => 'Accept all',
            'banner-texts-accept-essentials' => 'Only essentials',
            'banner-texts-accept-individual' => 'Choose',
            'individual-texts-save' => 'Save',
            'banner-layout-type' => 'dialog',
            'banner-layout-dialog-position' => 'bottomRight',
            'banner-design-bg' => '#123456',
            'banner-design-font-color' => '#fafafa',
            'banner-custom-css-css' => '.rcb-banner { color: red; }',
            'cookie-duration' => '100',
            'banner-sticky-links-enabled' => '1',
            'banner-sticky-links-alignment' => 'left',
        ];
        foreach ($options as $name => $value) {
            RealCookieBannerFixture::option($name, $value);
        }
    }

    private function sut(): RealCookieBannerSource
    {
        return $this->service(RealCookieBannerSource::class);
    }
}
