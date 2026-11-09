<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\Domain;

use ItalyCookieChoices\ConsentLog\Domain\ChoiceCount;
use ItalyCookieChoices\ConsentLog\Domain\ConsentStatistics;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentStatisticsTest extends WPTestCase
{
    private const OPTIONAL = ['preferences', 'statistics', 'marketing'];

    public function testItSortsTheChoicesOfAMonth(): void
    {
        $sut = new ConsentStatistics([
            new ChoiceCount('2026-11', ['necessary', 'preferences', 'statistics', 'marketing'], 5),
            new ChoiceCount('2026-11', ['necessary'], 3),
            new ChoiceCount('2026-11', ['necessary', 'statistics'], 2),
            new ChoiceCount('2026-11', [], 1),
        ], self::OPTIONAL);

        $this->assertSame(
            [[
                'month' => '2026-11',
                'choices' => 11,
                'acceptAll' => 5,
                'rejectAll' => 3,
                'partial' => 2,
                'withdrawals' => 1,
                'categories' => ['preferences' => 5, 'statistics' => 7, 'marketing' => 5],
            ]],
            $sut->months()
        );
    }

    public function testTheNewestMonthComesFirst(): void
    {
        $sut = new ConsentStatistics([
            new ChoiceCount('2026-09', ['necessary'], 1),
            new ChoiceCount('2026-11', ['necessary'], 1),
            new ChoiceCount('2026-10', ['necessary'], 1),
        ], self::OPTIONAL);

        $this->assertSame(['2026-11', '2026-10', '2026-09'], \array_column($sut->months(), 'month'));
    }

    public function testACategoryNoLongerInThePolicyIsLeftOut(): void
    {
        $sut = new ConsentStatistics([
            new ChoiceCount('2026-11', ['necessary', 'social'], 2),
        ], self::OPTIONAL);

        $month = $sut->months()[0] ?? null;

        $this->assertNotNull($month);
        $this->assertSame(2, $month['rejectAll']);
        $this->assertSame(['preferences' => 0, 'statistics' => 0, 'marketing' => 0], $month['categories']);
    }

    public function testWithoutChoicesThereAreNoMonths(): void
    {
        $this->assertSame([], (new ConsentStatistics([], self::OPTIONAL))->months());
    }
}
