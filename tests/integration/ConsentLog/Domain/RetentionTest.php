<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\Domain;

use ItalyCookieChoices\ConsentLog\Domain\Retention;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class RetentionTest extends WPTestCase
{
    public function testItKeepsTwelveMonthsByDefault(): void
    {
        $this->assertSame(12, (new Retention())->months());
    }

    public function testTheCutoffIsTheMonthsBeforeNow(): void
    {
        $now = new \DateTimeImmutable('2026-10-22 10:00:00', new \DateTimeZone('UTC'));

        $this->assertSame('2025-10-22 10:00:00', (new Retention())->cutoff($now)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-22 10:00:00', (new Retention(3))->cutoff($now)->format('Y-m-d H:i:s'));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public function invalidMonths(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-12];
        yield 'not in the list' => [7];
    }

    /**
     * @dataProvider invalidMonths
     */
    public function testOnlyTheListedMonthsAreValid(int $months): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Retention($months);
    }
}
