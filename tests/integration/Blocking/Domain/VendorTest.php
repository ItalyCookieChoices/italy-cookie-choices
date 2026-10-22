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
