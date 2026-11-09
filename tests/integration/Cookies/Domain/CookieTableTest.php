<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Cookies\Domain;

use ItalyCookieChoices\Cookies\Domain\Cookie;
use ItalyCookieChoices\Cookies\Domain\CookieTable;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieTableTest extends WPTestCase
{
    public function testACookieDescribesItself(): void
    {
        $cookie = new Cookie(' _ga ', 'Google Analytics', 'statistics', 'Tells the visitors apart.', '2 years');

        $this->assertSame('_ga', $cookie->name());
        $this->assertSame('Google Analytics', $cookie->provider());
        $this->assertSame('statistics', $cookie->category());
        $this->assertSame('Tells the visitors apart.', $cookie->purpose());
        $this->assertSame('2 years', $cookie->duration());
    }

    public function testACookieNeedsAName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Cookie(' ', 'Site', 'necessary', 'Purpose', 'Session');
    }

    public function testACookieNeedsAValidCategory(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Cookie('a', 'Site', 'Not Valid', 'Purpose', 'Session');
    }

    public function testTheTableGroupsTheCookiesByCategory(): void
    {
        $ga = new Cookie('_ga', 'Google Analytics', 'statistics', '', '2 years');
        $fbp = new Cookie('_fbp', 'Facebook', 'marketing', '', '3 months');
        $gaSession = new Cookie('_ga_*', 'Google Analytics', 'statistics', '', '2 years');

        $table = new CookieTable([$ga, $fbp, $gaSession]);

        $this->assertSame([$ga, $gaSession], $table->inCategory('statistics'));
        $this->assertSame([$fbp], $table->inCategory('marketing'));
        $this->assertSame([], $table->inCategory('preferences'));
        $this->assertFalse($table->isEmpty());
    }

    public function testACookieIsListedOnce(): void
    {
        $first = new Cookie('_ga', 'Google Analytics', 'statistics', 'First', '2 years');
        $second = new Cookie('_ga', 'Another plugin', 'marketing', 'Second', '1 year');

        $table = new CookieTable([$first, $second]);

        $this->assertSame([$first], $table->inCategory('statistics'));
        $this->assertSame([], $table->inCategory('marketing'));
    }

    public function testATableWithoutCookiesIsEmpty(): void
    {
        $this->assertTrue((new CookieTable([]))->isEmpty());
    }
}
