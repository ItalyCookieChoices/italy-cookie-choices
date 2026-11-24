<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Application;

use ItalyCookieChoices\Cookies\Application\CookieDurations;
use ItalyCookieChoices\Migration\Application\CookieLifetime;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieLifetimeTest extends WPTestCase
{
    /**
     * @return iterable<string, array{int|null, string}>
     */
    public function lifetimes(): iterable
    {
        yield 'session' => [null, 'Session'];
        yield 'nothing' => [0, ''];
        yield 'seconds' => [10, '1 minute'];
        yield 'minutes' => [1800, '30 minutes'];
        yield 'hours' => [7200, '120 minutes'];
        yield 'one day' => [86400, '1 day'];
        yield 'days' => [30 * 86400, '30 days'];
        yield 'three months' => [90 * 86400, '3 months'];
        yield 'one year' => [31536000, '1 year'];
        yield 'thirteen months' => [400 * 86400, '13 months'];
        yield 'two years' => [2 * 31536000, '2 years'];
    }

    /**
     * @dataProvider lifetimes
     */
    public function testItWritesTheLifetimeAsTheCookiePolicy(?int $seconds, string $text): void
    {
        $this->assertSame($text, (new CookieLifetime(new CookieDurations()))->text($seconds));
    }
}
