<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\CookieChoices;

use Italy_Cookie_Choices\Core\Cookie_Choices;
use ItalyStrap\Config\Config;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Overclokk\Cookie\Cookie;

final class PageCacheTest extends WPTestCase
{
    private const ADVANCED_CACHE = WP_CONTENT_DIR . '/advanced-cache.php';

    public function tearDown(): void
    {
        if (\file_exists(self::ADVANCED_CACHE)) {
            \unlink(self::ADVANCED_CACHE);
        }
        parent::tearDown();
    }

    public function testItDoesNothingWithoutAnAdvancedCacheDropIn(): void
    {
        $this->assertFalse($this->makeCookieChoices()->disable_w3tc_page_cache());
    }

    /**
     * Runs last in this class because the constants cannot be undefined.
     */
    public function testItDisablesThePageCacheWithAnAdvancedCacheDropIn(): void
    {
        \file_put_contents(self::ADVANCED_CACHE, '<?php');

        $this->assertNull($this->makeCookieChoices()->disable_w3tc_page_cache());
        $this->assertTrue(DONOTCACHEPAGE);
        $this->assertTrue(SID);
    }

    private function makeCookieChoices(): Cookie_Choices
    {
        return new Cookie_Choices(new Config([]), new Cookie());
    }
}
