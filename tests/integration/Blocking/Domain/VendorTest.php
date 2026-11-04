<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\Domain;

use ItalyCookieChoices\Blocking\Domain\Vendor;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class VendorTest extends WPTestCase
{
    public function testItHasAnIdAndACategory(): void
    {
        $vendor = new Vendor('youtube', 'marketing', ['youtube.com']);

        $this->assertSame('youtube', $vendor->id());
        $this->assertSame('marketing', $vendor->category());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function loadedUrls(): iterable
    {
        yield 'https' => ['https://youtube.com/embed/a'];
        yield 'subdomain' => ['https://www.youtube.com/embed/a'];
        yield 'protocol relative' => ['//www.youtube.com/embed/a'];
        yield 'http' => ['http://m.youtube.com'];
        yield 'uppercase' => ['HTTPS://WWW.YOUTUBE.COM/embed/a'];
        yield 'spaces' => [' https://www.youtube.com/embed/a '];
        yield 'path source' => ['https://www.facebook.com/plugins/like.php'];
        yield 'relative path' => ['/wp-content/plugins/tracker/tracker.js'];
    }

    /**
     * @dataProvider loadedUrls
     */
    public function testItLoadsItsSources(string $url): void
    {
        $vendor = new Vendor('custom', 'marketing', ['youtube.com', 'facebook.com/plugins', 'tracker/tracker.js']);

        $this->assertTrue($vendor->loads($url));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function otherUrls(): iterable
    {
        yield 'another domain ending the same' => ['https://notyoutube.com/embed/a'];
        yield 'another path' => ['https://www.facebook.com/share'];
        yield 'empty' => [''];
        yield 'site' => ['https://example.com/wp-includes/js/jquery.js'];
    }

    /**
     * @dataProvider otherUrls
     */
    public function testItDoesNotLoadOtherUrls(string $url): void
    {
        $vendor = new Vendor('custom', 'marketing', ['youtube.com', 'facebook.com/plugins']);

        $this->assertFalse($vendor->loads($url));
    }

    public function testItLoadsASourceFoundAfterAnotherMatch(): void
    {
        $vendor = new Vendor('youtube', 'marketing', ['youtube.com']);

        $this->assertTrue($vendor->loads('https://notyoutube.com/redirect/www.youtube.com'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function taggedUrls(): iterable
    {
        yield 'site domain' => ['https://example.com/metrics/?id=GTM-ABC'];
        yield 'relative' => ['/metrics/?id=GTM-ABC'];
        yield 'another parameter first' => ['https://example.com/metrics/?l=dataLayer&id=GTM-ABC'];
        yield 'lowercase' => ['https://example.com/metrics/?id=gtm-abc'];
        yield 'encoded ampersand' => ['https://example.com/metrics/?l=dataLayer&amp;id=GTM-ABC'];
    }

    /**
     * @dataProvider taggedUrls
     */
    public function testItLoadsTheTagsWithItsIdsOnAnyHost(string $url): void
    {
        $vendor = new Vendor('google-tag-manager', 'marketing', [], [], ['GTM-']);

        $this->assertTrue($vendor->loads($url));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function otherTaggedUrls(): iterable
    {
        yield 'another prefix' => ['https://example.com/metrics/?id=G-ABC'];
        yield 'the prefix inside the id' => ['https://example.com/metrics/?id=XGTM-ABC'];
        yield 'another parameter' => ['https://example.com/metrics/?gid=GTM-ABC'];
        yield 'without a query' => ['https://example.com/metrics/GTM-ABC'];
    }

    /**
     * @dataProvider otherTaggedUrls
     */
    public function testItDoesNotLoadTheTagsWithOtherIds(string $url): void
    {
        $vendor = new Vendor('google-tag-manager', 'marketing', [], [], ['GTM-']);

        $this->assertFalse($vendor->loads($url));
    }

    public function testItRunsTheScriptsWithItsSignatures(): void
    {
        $vendor = new Vendor('facebook', 'marketing', [], ['connect.facebook.net', 'fbq(']);

        $this->assertTrue($vendor->runs("!function(f){f.fbq=1}(window);fbq('init', '1');"));
        $this->assertTrue($vendor->runs("s.src='https://connect.facebook.net/en_US/fbevents.js'"));
        $this->assertFalse($vendor->runs('window.dataLayer = [];'));
    }

    public function testEmptySourcesAndSignaturesMatchNothing(): void
    {
        $vendor = new Vendor('custom', 'marketing', ['', '  '], ['', ' ']);

        $this->assertFalse($vendor->loads('https://example.com/a.js'));
        $this->assertFalse($vendor->runs('window.a = 1;'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function invalidVendors(): iterable
    {
        yield 'empty id' => ['', 'marketing'];
        yield 'uppercase id' => ['YouTube', 'marketing'];
        yield 'quote in the id' => ['a"b', 'marketing'];
        yield 'empty category' => ['youtube', ''];
        yield 'quote in the category' => ['youtube', 'a"b'];
    }

    /**
     * @dataProvider invalidVendors
     */
    public function testTheIdAndTheCategoryMustBeSlugs(string $id, string $category): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Vendor($id, $category, ['example.com']);
    }
}
