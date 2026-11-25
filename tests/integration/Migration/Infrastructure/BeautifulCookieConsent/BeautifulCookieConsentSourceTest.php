<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Infrastructure\BeautifulCookieConsent;

use ItalyCookieChoices\Migration\Application\MigrationSources;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Migration\Infrastructure\BeautifulCookieConsent\BeautifulCookieConsentSource;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Migration\BeautifulCookieConsentFixture;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class BeautifulCookieConsentSourceTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        \update_option('WPLANG', '');
    }

    public function testItIsFoundWithItsVersionOrItsBanner(): void
    {
        $this->assertFalse($this->sut()->isFound());

        BeautifulCookieConsentFixture::option('db_version', '4.11.0');

        $this->assertTrue($this->sut()->isFound());
        $this->assertSame('beautiful-cookie-consent', $this->sut()->id());
        $this->assertSame('Beautiful Cookie Consent', $this->sut()->name());
        $this->assertContains(
            'beautiful-cookie-consent',
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
            'post_name' => 'privacy',
            'post_title' => 'Privacy',
        ]);
        $this->assertIsInt($policy);
        $url = \get_permalink($policy);
        $this->assertIsString($url);
        BeautifulCookieConsentFixture::option('activate_banner', '1');
        BeautifulCookieConsentFixture::option('microsoftUETconsentMode', '1');
        BeautifulCookieConsentFixture::option('gtm_id', 'GTM-ABC1234');
        BeautifulCookieConsentFixture::banner([
            'type' => 'detailed',
            'position' => 'bottom-left',
            'content' => [
                'message' => 'We use <b>cookies</b>.',
                'allow' => 'Allow all',
                'deny' => 'Deny',
                'savesettings' => 'Save',
                'link' => 'Our policy',
                'href' => $url,
                'linksecond' => 'Imprint',
                'hrefsecond' => 'https://example.org/imprint',
                'policy' => 'Cookies',
            ],
            'palette' => ['popup' => ['background' => '#04194e', 'text' => '#ffffff']],
            'revokable' => true,
            'positionRevokeButton' => 'bottom-left',
            'cookie' => ['expiryDays' => 180, 'domain' => '.example.org'],
            'activateConsentMode' => true,
            'microsoftClarityEnabled' => false,
            'cookietypes' => [
                ['label' => 'Necessary', 'checked' => 'checked', 'disabled' => 'disabled', 'cookie_suffix' => 'tech'],
                ['label' => 'Analytics', 'checked' => '', 'disabled' => '', 'cookie_suffix' => 'statistics'],
                ['label' => 'Ads', 'checked' => '', 'disabled' => '', 'cookie_suffix' => 'ads'],
            ],
        ]);

        $imported = $this->sut()->read();
        $settings = $imported->settings();

        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('We use <b>cookies</b>.', $settings['text'] ?? null);
        $this->assertSame('Allow all', $settings['button_text'] ?? null);
        $this->assertSame('Deny', $settings['reject_button_text'] ?? null);
        $this->assertSame('Save', $settings['save_button_text'] ?? null);
        $this->assertSame('privacy', $settings['slug'] ?? null);
        $this->assertSame($url, $settings['url'] ?? null);
        $this->assertSame('Our policy', $settings['anchor_text'] ?? null);
        $this->assertSame('https://example.org/imprint', $settings['legal_notice_url'] ?? null);
        $this->assertSame('Imprint', $settings['legal_notice_text'] ?? null);
        $this->assertSame('4', $settings['banner'] ?? null);
        $this->assertSame('#04194e', $settings['banner_bg'] ?? null);
        $this->assertSame('#ffffff', $settings['banner_text_color'] ?? null);
        $this->assertSame('1', $settings['floating_button'] ?? null);
        $this->assertSame('left', $settings['floating_button_position'] ?? null);
        $this->assertSame('Cookies', $settings['floating_button_label'] ?? null);
        $this->assertSame('182', $settings['consent_duration'] ?? null);
        $this->assertSame('example.org', $settings['cookie_domain'] ?? null);
        $this->assertSame('1', $settings['consent_mode'] ?? null);
        $this->assertSame('1', $settings['uet_consent_mode'] ?? null);
        $this->assertArrayNotHasKey('clarity_consent_mode', $settings);
        $this->assertSame('GTM-ABC1234', $settings['gtm_container_id'] ?? null);
        $this->assertSame(
            [
                'necessary' => ['label' => 'Necessary'],
                'statistics' => ['label' => 'Analytics'],
                'marketing' => ['label' => 'Ads'],
            ],
            $settings['categories'] ?? null
        );
        $this->assertSame(['google-tag-manager'], $imported->vendorsInUse());
    }

    public function testTheInformationBannerAndThePositions(): void
    {
        BeautifulCookieConsentFixture::banner([
            'type' => 'info',
            'position' => 'centered',
            'content' => ['dismiss' => 'Got it', 'allow' => 'Allow'],
            'revokable' => false,
        ]);
        $info = $this->sut()->read()->settings();
        BeautifulCookieConsentFixture::banner(['position' => 'top']);
        $top = $this->sut()->read()->settings();

        $this->assertArrayNotHasKey('active', $info);
        $this->assertSame('Got it', $info['button_text'] ?? null);
        $this->assertSame('2', $info['banner'] ?? null);
        $this->assertArrayNotHasKey('floating_button', $info);
        $this->assertSame('1', $top['banner'] ?? null);
    }

    private function sut(): BeautifulCookieConsentSource
    {
        return $this->service(BeautifulCookieConsentSource::class);
    }
}
