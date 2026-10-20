<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;

use function Italy_Cookie_Choices\Core\is_bot;
use function Italy_Cookie_Choices\Core\is_debug;
use function Italy_Cookie_Choices\Core\is_sitemaps_xml;

final class GeneralFunctionsTest extends WPTestCase
{
    private array $server = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
    }

    public function tearDown(): void
    {
        $_SERVER = $this->server;
        parent::tearDown();
    }

    public function testIsSitemapsXmlReturnsNullWithoutRequestUri(): void
    {
        unset($_SERVER['REQUEST_URI']);

        $this->assertNull(is_sitemaps_xml());
    }

    public function sitemapUris(): iterable
    {
        yield 'xml sitemap' => ['/sitemap.xml'];
        yield 'yoast index' => ['/sitemap_index.xml'];
        yield 'core sitemap' => ['/wp-sitemap-posts-post-1.xml'];
        yield 'xsl stylesheet' => ['/main-sitemap.xsl'];
        yield 'upper case' => ['/SITEMAP.xml'];
    }

    /**
     * @dataProvider sitemapUris
     */
    public function testIsSitemapsXmlReturnsTrueForSitemapUris(string $uri): void
    {
        $_SERVER['REQUEST_URI'] = $uri;

        $this->assertTrue(is_sitemaps_xml());
    }

    public function notSitemapUris(): iterable
    {
        yield 'home' => ['/'];
        yield 'xml without sitemap' => ['/feed.xml'];
        yield 'sitemap page' => ['/sitemap/'];
        yield 'sitemap with other extension' => ['/sitemap.html'];
        yield 'xml in the middle' => ['/sitemap.xml/page'];
    }

    /**
     * @dataProvider notSitemapUris
     */
    public function testIsSitemapsXmlReturnsNullForOtherUris(string $uri): void
    {
        $_SERVER['REQUEST_URI'] = $uri;

        $this->assertNull(is_sitemaps_xml());
    }

    public function testIsBotReturnsFalseWithoutUserAgent(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);

        $this->assertFalse(is_bot());
    }

    public function testIsBotReturnsFalseWithEmptyUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = '';

        $this->assertFalse(is_bot());
    }

    public function botUserAgents(): iterable
    {
        yield 'googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'];
        yield 'bingbot' => ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'];
        yield 'facebook' => ['facebookexternalhit/1.1'];
        yield 'mixed case list entry' => ['Mozilla/5.0 (compatible; Yahoo! Slurp)'];
        yield 'generic crawler' => ['SomeCrawler/1.0'];
        yield 'pingdom' => ['Pingdom.com_bot_version_1.4'];
    }

    /**
     * @dataProvider botUserAgents
     */
    public function testIsBotReturnsTrueForKnownBots(string $userAgent): void
    {
        $_SERVER['HTTP_USER_AGENT'] = $userAgent;

        $this->assertTrue(is_bot());
    }

    public function testIsBotReturnsFalseForBrowsers(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36';

        $this->assertFalse(is_bot());
    }

    public function testIsBotListCanBeFiltered(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'MyCustomAgent/1.0';
        \add_filter('icc_bots', static function (array $bots): array {
            $bots[] = 'MyCustomAgent';
            return $bots;
        });

        $this->assertTrue(is_bot());
    }

    public function testIsBotListFilterCanRemoveBots(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1';
        \add_filter('icc_bots', static fn(): array => ['nothing-matches-this']);

        $this->assertFalse(is_bot());
    }

    public function testIsDebugFollowsWpDebug(): void
    {
        $this->assertSame(\defined('WP_DEBUG') && WP_DEBUG, is_debug());
    }
}
