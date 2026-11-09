<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Cookies\Application;

use ItalyCookieChoices\Cookies\Application\CookieDurations;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieDurationsTest extends WPTestCase
{
    public function testItDescribesTheDurations(): void
    {
        $sut = new CookieDurations();

        $this->assertSame('Session', $sut->session());
        $this->assertSame('30 minutes', $sut->minutes(30));
        $this->assertSame('1 day', $sut->days(1));
        $this->assertSame('182 days', $sut->days(182));
        $this->assertSame('1 month', $sut->months(1));
        $this->assertSame('13 months', $sut->months(13));
        $this->assertSame('2 years', $sut->years(2));
    }
}
