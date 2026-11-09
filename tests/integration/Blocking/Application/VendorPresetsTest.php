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
        yield 'Google tag served from the site' => [
            'https://example.com/metrics/?id=G-1',
            'google-analytics',
            'statistics',
        ];
        yield 'Google Ads tag served from the site' => [
            'https://example.com/metrics/?id=AW-1',
            'google-ads',
            'marketing',
        ];
        yield 'Google Ads tag' => ['https://www.googletagmanager.com/gtag/js?id=AW-1', 'google-ads', 'marketing'];
        yield 'Google Ads conversion' => [
            'https://www.googleadservices.com/pagead/conversion.js',
            'google-ads',
            'marketing',
        ];
        yield 'Google Ads remarketing frame' => [
            'https://googleads.g.doubleclick.net/pagead/viewthroughconversion/1/',
            'google-ads',
            'marketing',
        ];
        yield 'AdSense' => [
            'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1',
            'google-adsense',
            'marketing',
        ];
        yield 'Microsoft Clarity' => ['https://www.clarity.ms/tag/abc123', 'microsoft-clarity', 'statistics'];
        yield 'Microsoft Advertising' => ['https://bat.bing.com/bat.js', 'microsoft-advertising', 'marketing'];
        yield 'Pinterest tag' => ['https://s.pinimg.com/ct/core.js', 'pinterest', 'marketing'];
        yield 'Pinterest widgets' => ['https://assets.pinterest.com/js/pinit.js', 'pinterest', 'marketing'];
        yield 'Snapchat pixel' => ['https://sc-static.net/scevent.min.js', 'snapchat', 'marketing'];
        yield 'Reddit pixel' => ['https://www.redditstatic.com/ads/pixel.js', 'reddit', 'marketing'];
        yield 'HubSpot tracking' => ['https://js.hs-scripts.com/123.js', 'hubspot', 'marketing'];
        yield 'HubSpot chat' => ['https://js.usemessages.com/conversations-embed.js', 'hubspot', 'marketing'];
        yield 'Matomo Cloud' => [
            'https://cdn.matomo.cloud/example.matomo.cloud/matomo.js',
            'matomo-cloud',
            'statistics',
        ];
        yield 'reCAPTCHA' => ['https://www.google.com/recaptcha/api.js', 'google-recaptcha', 'marketing'];
        yield 'reCAPTCHA from recaptcha.net' => [
            'https://www.recaptcha.net/recaptcha/api.js',
            'google-recaptcha',
            'marketing',
        ];
        yield 'Google Tag Manager served from the site' => [
            '/metrics/?id=GTM-1',
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
        yield 'Google Tag Manager served from the site' => [
            "(function(w,d,s,l,i){w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});"
                . "j.src='/metrics/?id='+i+dl;})(window,document,'script','dataLayer','GTM-1');",
            'google-tag-manager',
        ];
        yield 'Microsoft Clarity' => [
            '(function(c,l,a,r,i,t,y){t=l.createElement(r);t.src="https://www.clarity.ms/tag/"+i;})'
                . '(window, document, "clarity", "script", "abc123");',
            'microsoft-clarity',
        ];
        yield 'Microsoft Advertising' => [
            '(function(w,d,t,r,u){n=d.createElement(t);n.src=r;})'
                . '(window,document,"script","//bat.bing.com/bat.js","uetq");',
            'microsoft-advertising',
        ];
        yield 'Pinterest tag' => [
            '!function(e){t.src=e;}("https://s.pinimg.com/ct/core.js");pintrk("load", "123");',
            'pinterest',
        ];
        yield 'Snapchat pixel' => [
            "(function(e,t,n){r.src=n;})(window,document,'https://sc-static.net/scevent.min.js');snaptr('init', 'a');",
            'snapchat',
        ];
        yield 'Reddit pixel' => [
            '!function(w,d){t.src="https://www.redditstatic.com/ads/pixel.js";}(window,document);rdt("init","a2_1");',
            'reddit',
        ];
        yield 'AdSense unit' => ['(adsbygoogle = window.adsbygoogle || []).push({});', 'google-adsense'];
        yield 'Matomo Cloud' => [
            "var _paq = window._paq = window._paq || [];"
                . "g.src='https://cdn.matomo.cloud/example.matomo.cloud/matomo.js';",
            'matomo-cloud',
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
        yield 'HubSpot forms' => ['https://js.hsforms.net/forms/embed/v2.js'];
        yield 'Matomo on the site' => ['https://example.com/matomo/matomo.js'];
        yield 'Google Fonts' => ['https://fonts.googleapis.com/css2?family=Inter'];
    }

    /**
     * @dataProvider siteScripts
     */
    public function testItDoesNotBlockTheScriptsTheSiteNeeds(string $url): void
    {
        $this->assertNull($this->vendors()->loading($url));
    }

    public function testMatomoOnTheSiteIsNotBlocked(): void
    {
        $this->assertNull($this->vendors()->running(
            "var _paq = window._paq = window._paq || [];var u='https://example.com/matomo/';"
        ));
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
