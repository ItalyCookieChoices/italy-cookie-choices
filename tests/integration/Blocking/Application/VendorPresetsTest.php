<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\Application;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Blocking\Domain\Vendors;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class VendorPresetsTest extends WPTestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public function sources(): iterable
    {
        yield 'Google Analytics 4' => [
            'https://www.googletagmanager.com/gtag/js?id=G-1',
            'google-analytics',
            'statistics',
        ];
        yield 'Universal Analytics' => [
            'https://www.google-analytics.com/analytics.js',
            'google-analytics',
            'statistics',
        ];
        yield 'Google Tag Manager' => [
            'https://www.googletagmanager.com/gtm.js?id=GTM-1',
            'google-tag-manager',
            'marketing',
        ];
        yield 'Google Tag Manager frame' => [
            'https://www.googletagmanager.com/ns.html?id=GTM-1',
            'google-tag-manager',
            'marketing',
        ];
        yield 'Hotjar' => ['https://static.hotjar.com/c/hotjar-1.js', 'hotjar', 'statistics'];
        yield 'YouTube' => ['https://www.youtube.com/embed/a', 'youtube', 'marketing'];
        yield 'YouTube without cookies' => ['https://www.youtube-nocookie.com/embed/a', 'youtube', 'marketing'];
        yield 'Vimeo' => ['https://player.vimeo.com/video/1', 'vimeo', 'marketing'];
        yield 'Google Maps' => ['https://www.google.com/maps/embed?pb=1', 'google-maps', 'marketing'];
        yield 'Google Maps API' => ['https://maps.googleapis.com/maps/api/js?key=a', 'google-maps', 'marketing'];
        yield 'Facebook SDK' => ['https://connect.facebook.net/en_US/sdk.js', 'facebook', 'marketing'];
        yield 'Facebook page plugin' => ['https://www.facebook.com/plugins/page.php?href=a', 'facebook', 'marketing'];
        yield 'Instagram' => ['https://www.instagram.com/embed.js', 'instagram', 'marketing'];
        yield 'X' => ['https://platform.twitter.com/widgets.js', 'x', 'marketing'];
        yield 'LinkedIn' => ['https://snap.licdn.com/li.lms-analytics/insight.min.js', 'linkedin', 'marketing'];
        yield 'TikTok' => ['https://www.tiktok.com/embed/v2/1', 'tiktok', 'marketing'];
        yield 'Spotify' => ['https://open.spotify.com/embed/track/1', 'spotify', 'marketing'];
        yield 'SoundCloud' => ['https://w.soundcloud.com/player/?url=a', 'soundcloud', 'marketing'];
    }

    /**
     * @dataProvider sources
     */
    public function testItBlocksTheCommonVendors(string $url, string $id, string $category): void
    {
        $vendor = $this->vendors()->loading($url);

        $this->assertNotNull($vendor);
        $this->assertSame($id, $vendor->id());
        $this->assertSame($category, $vendor->category());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function inlineScripts(): iterable
    {
        yield 'Google Tag Manager' => [
            "(function(w,d,s,l,i){j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;})(window,document,"
                . "'script','dataLayer','GTM-1');",
            'google-tag-manager',
        ];
        yield 'Facebook Pixel' => [
            "!function(f,b,e,v){t.src=v}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');",
            'facebook',
        ];
        yield 'Hotjar' => ["(function(h,o,t,j,a,r){r.src=t+h._hjSettings.hjid+j})(window,document,"
            . "'https://static.hotjar.com/c/hotjar-','.js?sv=');", 'hotjar'];
    }

    /**
     * @dataProvider inlineScripts
     */
    public function testItBlocksTheCommonSnippets(string $script, string $id): void
    {
        $this->assertSame($id, $this->vendors()->running($script)?->id());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function siteScripts(): iterable
    {
        yield 'jQuery' => ['https://example.com/wp-includes/js/jquery/jquery.min.js'];
        yield 'reCAPTCHA' => ['https://www.google.com/recaptcha/api.js'];
        yield 'Google Fonts' => ['https://fonts.googleapis.com/css2?family=Inter'];
    }

    /**
     * @dataProvider siteScripts
     */
    public function testItDoesNotBlockTheScriptsTheSiteNeeds(string $url): void
    {
        $this->assertNull($this->vendors()->loading($url));
    }

    public function testASocialLinkInTheScriptDataIsNotBlocked(): void
    {
        $this->assertNull($this->vendors()->running('var theme = {"youtube":"https://www.youtube.com/@channel"};'));
    }

    public function testEveryPresetHasAName(): void
    {
        $sut = new VendorPresets();

        foreach ($sut->all() as $vendor) {
            $this->assertNotSame($vendor->id(), $sut->name($vendor->id()), $vendor->id());
        }

        $this->assertSame('YouTube', $sut->name('youtube'));
        $this->assertSame('custom', $sut->name('custom'));
    }

    private function vendors(): Vendors
    {
        return new Vendors((new VendorPresets())->all());
    }
}
