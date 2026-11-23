<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\Application\ServerCookies;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ServerCookiesTest extends WPTestCase
{
    private const NOW = '2026-11-22T10:00:00+00:00';

    public function testItReadsTheCookiesOfTheSetCookieHeaders(): void
    {
        $this->assertSame(
            [
                ['name' => 'wp_lang', 'path' => '/', 'duration' => 3600, 'httpOnly' => false],
                ['name' => 'session', 'path' => '', 'duration' => null, 'httpOnly' => true],
                ['name' => 'cart', 'path' => '/shop/', 'duration' => 86400, 'httpOnly' => true],
            ],
            $this->cookiesOf([
                'Content-Type: text/html; charset=UTF-8',
                'Set-Cookie: wp_lang=it_IT; expires=Sun, 22 Nov 2026 11:00:00 GMT; path=/',
                'Set-Cookie: session=abc; HttpOnly',
                'set-cookie: cart=1; Max-Age=86400; Expires=Sun, 22 Nov 2026 11:00:00 GMT; Path=/shop/; HttpOnly',
            ])
        );
    }

    public function testTheRemovedCookiesAreSkipped(): void
    {
        $this->assertSame(
            [],
            $this->cookiesOf([
                'Set-Cookie: old=deleted; expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; path=/',
                'Set-Cookie: past=1; expires=Sat, 21 Nov 2026 10:00:00 GMT',
                'Set-Cookie: =nameless',
                'Set-Cookie: novalue',
            ])
        );
    }

    public function testACookieSetTwiceKeepsTheLastWrite(): void
    {
        $this->assertSame(
            [['name' => 'pll_language', 'path' => '/', 'duration' => 60, 'httpOnly' => false]],
            $this->cookiesOf([
                'Set-Cookie: pll_language=en; Max-Age=30; path=/',
                'Set-Cookie: pll_language=it; Max-Age=60; path=/',
            ])
        );
    }

    /**
     * @param list<string> $headers
     * @return list<array{name: string, path: string, duration: int|null, httpOnly: bool}>
     */
    private function cookiesOf(array $headers): array
    {
        return (new ServerCookies())->from($headers, new \DateTimeImmutable(self::NOW));
    }
}
