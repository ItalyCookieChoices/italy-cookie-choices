<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\Domain;

use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Blocking\Domain\Vendors;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class VendorsTest extends WPTestCase
{
    public function testItFindsTheVendorLoadingAUrl(): void
    {
        $vendors = $this->vendors();

        $this->assertSame('youtube', $vendors->loading('https://www.youtube.com/embed/a')?->id());
        $this->assertSame('analytics', $vendors->loading('https://www.google-analytics.com/analytics.js')?->id());
        $this->assertNull($vendors->loading('https://example.com/script.js'));
    }

    public function testItFindsTheVendorRunningAnInlineScript(): void
    {
        $vendors = $this->vendors();

        $this->assertSame('analytics', $vendors->running("ga('create', 'UA-1');")?->id());
        $this->assertNull($vendors->running('window.a = 1;'));
    }

    public function testTheFirstVendorWins(): void
    {
        $vendors = new Vendors([
            new Vendor('first', 'statistics', ['example.com']),
            new Vendor('second', 'marketing', ['example.com']),
        ]);

        $this->assertSame('first', $vendors->loading('https://example.com/a.js')?->id());
    }

    public function testTheAllowedTextsAreNeverBlocked(): void
    {
        $vendors = new Vendors(
            \array_values(\iterator_to_array($this->vendors())),
            ['youtube.com/embed/public', ' ', "ga('create'"]
        );

        $this->assertNull($vendors->loading('https://www.youtube.com/embed/public-video'));
        $this->assertSame('youtube', $vendors->loading('https://www.youtube.com/embed/other')?->id());
        $this->assertNull($vendors->running("ga('create', 'UA-1');"));
    }

    public function testItIteratesTheVendors(): void
    {
        $ids = \array_map(static fn(Vendor $vendor): string => $vendor->id(), \iterator_to_array($this->vendors()));

        $this->assertSame(['youtube', 'analytics'], $ids);
    }

    private function vendors(): Vendors
    {
        return new Vendors([
            new Vendor('youtube', 'marketing', ['youtube.com']),
            new Vendor('analytics', 'statistics', ['google-analytics.com'], ["ga('create'"]),
        ]);
    }
}
