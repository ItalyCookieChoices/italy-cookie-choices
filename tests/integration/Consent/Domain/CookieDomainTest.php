<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\Domain;

use ItalyCookieChoices\Consent\Domain\CookieDomain;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieDomainTest extends WPTestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public function domains(): iterable
    {
        yield 'the host' => ['www.example.com', 'www.example.com', 'www.example.com'];
        yield 'a parent of the host' => ['example.com', 'www.example.com', 'example.com'];
        yield 'with the dot of the old browsers' => ['.Example.COM ', 'shop.example.com', 'example.com'];
        yield 'a parent of a host in capitals' => ['example.com', 'WWW.Example.com', 'example.com'];
        yield 'empty' => ['', 'www.example.com', ''];
        yield 'another domain' => ['example.org', 'www.example.com', ''];
        yield 'the end of a label' => ['ample.com', 'www.example.com', ''];
        yield 'a subdomain of the host' => ['shop.example.com', 'example.com', ''];
        yield 'a top level domain' => ['com', 'www.example.com', ''];
        yield 'not a domain' => ['example .com', 'www.example.com', ''];
    }

    /**
     * @dataProvider domains
     */
    public function testTheDomainIsTheHostOrOneOfItsParents(string $domain, string $host, string $expected): void
    {
        $this->assertSame($expected, (new CookieDomain($domain))->forHost($host));
    }
}
