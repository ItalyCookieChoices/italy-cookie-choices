<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\Infrastructure;

use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Blocking\Domain\Vendors;
use ItalyCookieChoices\Blocking\Infrastructure\HtmlBlocker;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class HtmlBlockerTest extends WPTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public function blockedHtml(): iterable
    {
        yield 'script file' => [
            '<script src="https://www.google-analytics.com/analytics.js" async></script>',
            '<script data-category="statistics" type="text/plain" '
                . 'src="https://www.google-analytics.com/analytics.js" async></script>',
        ];
        yield 'script with a JavaScript type' => [
            '<script type="text/javascript" src="https://www.google-analytics.com/analytics.js"></script>',
            '<script data-category="statistics" type="text/plain" '
                . 'src="https://www.google-analytics.com/analytics.js"></script>',
        ];
        yield 'module' => [
            '<script type="module" src="https://www.google-analytics.com/module.js"></script>',
            '<script data-category="statistics" data-icc-type="module" type="text/plain" '
                . 'src="https://www.google-analytics.com/module.js"></script>',
        ];
        yield 'inline script' => [
            "<script>ga('create', 'UA-1');</script>",
            "<script data-category=\"statistics\" type=\"text/plain\">ga('create', 'UA-1');</script>",
        ];
        yield 'frame' => [
            '<iframe width="560" height="315" src="https://www.youtube.com/embed/a?b=1&amp;c=2" allowfullscreen>'
                . '</iframe>',
            '<iframe data-category="marketing" data-icc-src="https://www.youtube.com/embed/a?b=1&amp;c=2" '
                . 'width="560" height="315"  allowfullscreen></iframe>',
        ];
        yield 'embed' => [
            '<embed src="https://www.youtube.com/v/a" type="application/x-shockwave-flash">',
            '<embed data-category="marketing" data-icc-src="https://www.youtube.com/v/a"  '
                . 'type="application/x-shockwave-flash">',
        ];
        yield 'uppercase tags' => [
            '<IFRAME SRC="https://www.youtube.com/embed/a"></IFRAME>',
            '<IFRAME data-category="marketing" data-icc-src="https://www.youtube.com/embed/a" ></IFRAME>',
        ];
    }

    /**
     * @dataProvider blockedHtml
     */
    public function testItBlocksTheVendors(string $html, string $expected): void
    {
        $this->assertSame($expected, $this->makeSut()->block($html, $this->vendors()));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function untouchedHtml(): iterable
    {
        yield 'no script and no frame' => ['<p>Text</p>'];
        yield 'site script' => ['<script src="https://example.com/wp-includes/js/jquery.js"></script>'];
        yield 'site inline script' => ['<script>window.site = true;</script>'];
        yield 'site frame' => ['<iframe src="https://example.com/embed/1"></iframe>'];
        yield 'frame without src' => ['<iframe srcdoc="<p>a</p>"></iframe>'];
        yield 'JSON data' => ["<script type=\"application/ld+json\">{\"url\": \"ga('create'\"}</script>"];
        yield 'already blocked script' => [
            '<script type="text/plain" data-category="statistics" '
                . 'src="https://www.google-analytics.com/analytics.js"></script>',
        ];
        yield 'plugin script' => [
            "<script id=\"italy-cookie-choices-consent-js-before\">ga('create', 'UA-1');</script>",
        ];
        yield 'text that looks like a script' => [
            '<p>Use &lt;script src="https://www.google-analytics.com/analytics.js"&gt;</p>',
        ];
        yield 'script in a comment' => [
            '<!-- <script src="https://www.google-analytics.com/analytics.js"></script> -->',
        ];
    }

    /**
     * @dataProvider untouchedHtml
     */
    public function testItLeavesTheOtherHtmlAlone(string $html): void
    {
        $this->assertSame($html, $this->makeSut()->block($html, $this->vendors()));
    }

    public function testBlockingTwiceChangesNothingMore(): void
    {
        $html = '<p>Video</p><iframe src="https://www.youtube.com/embed/a"></iframe>'
            . "<script>ga('create', 'UA-1');</script>";
        $once = $this->makeSut()->block($html, $this->vendors());

        $this->assertSame($once, $this->makeSut()->block($once, $this->vendors()));
    }

    public function testTheAuthorCategoryWinsOverTheVendors(): void
    {
        $html = '<script data-category="preferences" src="https://www.google-analytics.com/analytics.js"></script>'
            . '<iframe data-category="statistics" src="https://example.com/embed/1"></iframe>';

        $this->assertSame(
            '<script type="text/plain" data-category="preferences" '
                . 'src="https://www.google-analytics.com/analytics.js"></script>'
            . '<iframe data-icc-src="https://example.com/embed/1" data-category="statistics" ></iframe>',
            $this->makeSut()->block($html, $this->vendors())
        );
    }

    public function testTheCallerCategoryBlocksEveryScript(): void
    {
        $html = '<script>var settings = {};</script><script src="https://example.com/app.js"></script>';

        $this->assertSame(
            '<script data-category="statistics" type="text/plain">var settings = {};</script>'
            . '<script data-category="statistics" type="text/plain" src="https://example.com/app.js"></script>',
            $this->makeSut()->block($html, $this->vendors(), 'statistics')
        );
    }

    public function testItBlocksAWholePageAndKeepsTheRest(): void
    {
        $html = '<!DOCTYPE html><html><head><script src="https://www.google-analytics.com/analytics.js"></script>'
            . '<title>Page</title></head><body><p>Text</p>'
            . '<iframe src="https://www.youtube.com/embed/a"></iframe><footer>Footer</footer></body></html>';

        $blocked = $this->makeSut()->block($html, $this->vendors());

        $this->assertStringContainsString('<script data-category="statistics" type="text/plain"', $blocked);
        $this->assertStringContainsString('<iframe data-category="marketing" '
            . 'data-icc-src="https://www.youtube.com/embed/a" >', $blocked);
        $this->assertStringContainsString('<title>Page</title></head><body><p>Text</p>', $blocked);
        $this->assertStringEndsWith('<footer>Footer</footer></body></html>', $blocked);
    }

    private function vendors(): Vendors
    {
        return new Vendors([
            new Vendor('youtube', 'marketing', ['youtube.com']),
            new Vendor('analytics', 'statistics', ['google-analytics.com'], ["ga('create'"]),
        ]);
    }

    private function makeSut(): HtmlBlocker
    {
        return new HtmlBlocker();
    }
}
