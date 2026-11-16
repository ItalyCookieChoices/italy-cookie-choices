<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Blocking\Domain\Vendors;
use ItalyCookieChoices\Scan\Application\PageAnalyzer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class PageAnalyzerTest extends WPTestCase
{
    private const SITE = 'example.com';

    public function testItFindsTheVendorsOfTheScriptsAndOfTheFrames(): void
    {
        $findings = $this->analyze(
            '<script src="https://www.googletagmanager.com/gtag/js?id=G-ABC"></script>'
            . '<iframe src="https://www.youtube.com/embed/abc"></iframe>'
            . '<script>fbq("init", "1");'
            . ' !function(){ var s = "https://connect.facebook.net/en_US/fbevents.js"; }</script>'
        );

        $this->assertSame(['google-analytics', 'youtube', 'facebook'], $findings->vendors());
        $this->assertSame([], $findings->hosts());
    }

    public function testItFindsTheVendorsAlreadyBlockedByThePlugin(): void
    {
        $findings = $this->analyze(
            '<script type="text/plain" data-category="statistics" src="https://static.hotjar.com/c/hotjar.js"></script>'
            . '<iframe data-icc-src="https://player.vimeo.com/video/1" data-category="marketing"></iframe>'
        );

        $this->assertSame(['hotjar', 'vimeo'], $findings->vendors());
    }

    public function testItFindsTheTrackingPixels(): void
    {
        $findings = $this->analyze('<img src="https://www.facebook.com/tr?id=1&ev=PageView" alt="">');

        $this->assertSame(['facebook'], $findings->vendors());
    }

    public function testTheScriptsAndFramesOfUnknownThirdPartiesAreTheirHosts(): void
    {
        $findings = $this->analyze(
            '<script src="https://cdn.tracker.example.org/t.js"></script>'
            . '<script src="//widgets.example.net/w.js"></script>'
            . '<iframe src="https://forms.example.net/embed"></iframe>'
            . '<script src="https://cdn.tracker.example.org/other.js"></script>'
            . '<img src="https://images.example.org/photo.jpg" alt="">'
        );

        $this->assertSame([], $findings->vendors());
        $this->assertSame(['cdn.tracker.example.org', 'widgets.example.net', 'forms.example.net'], $findings->hosts());
    }

    public function testTheSiteAndItsSubdomainsAreNotThirdParties(): void
    {
        $findings = $this->analyze(
            '<script src="/wp-includes/js/jquery.js"></script>'
            . '<script src="https://example.com/wp-content/themes/t/app.js"></script>'
            . '<script src="https://cdn.example.com/app.js"></script>'
            . '<iframe src="https://www.example.com/embed"></iframe>'
        );

        $this->assertSame([], $findings->hosts());
    }

    private function analyze(string $html): \ItalyCookieChoices\Scan\Domain\Findings
    {
        return (new PageAnalyzer())->analyze(
            $html,
            new Vendors([...(new VendorPresets())->all(), new Vendor('own', 'statistics', ['example.com/stats'])]),
            self::SITE
        );
    }
}
