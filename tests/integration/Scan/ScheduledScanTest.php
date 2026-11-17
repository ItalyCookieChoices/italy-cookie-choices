<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Blocking\Application\VendorSettings;
use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Scan\Application\PageAnalyzer;
use ItalyCookieChoices\Scan\Application\ScanReports;
use ItalyCookieChoices\Scan\Application\ScanSchedule;
use ItalyCookieChoices\Scan\Application\ScanTargets;
use ItalyCookieChoices\Scan\Application\ScheduledScan;
use ItalyCookieChoices\Scan\Application\SiteScanner;
use ItalyCookieChoices\Scan\Domain\Frequency;
use ItalyCookieChoices\Scan\Domain\Schedule;
use ItalyCookieChoices\Scan\Domain\Selection;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Scan\FakePageFetcher;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScheduledScanTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['disabled_vendors' => ['hotjar']]);
    }

    public function testTheScheduleIsSavedInItsOwnOption(): void
    {
        $schedule = new Schedule(Frequency::Weekly, new Selection(true, ['page'], [3], [7]), true);

        (new ScanSchedule())->save($schedule);

        $this->assertEquals($schedule, (new ScanSchedule())->get());
        $this->assertSame(
            [
                'frequency' => 'weekly',
                'home' => true,
                'postTypes' => ['page'],
                'categories' => [3],
                'posts' => [7],
                'email' => true,
            ],
            \get_option(ScanSchedule::OPTION)
        );
        $this->assertArrayNotHasKey('frequency', (array) \get_option(Schema::OPTION));
    }

    public function testWithoutAScheduleTheScanIsOff(): void
    {
        $this->assertEquals(new Schedule(), (new ScanSchedule())->get());
        \update_option(ScanSchedule::OPTION, ['frequency' => 'hourly', 'postTypes' => 'page', 'posts' => ['x', 4]]);

        $schedule = (new ScanSchedule())->get();

        $this->assertSame(Frequency::Off, $schedule->frequency);
        $this->assertSame([], $schedule->selection->postTypes);
        $this->assertSame([4], $schedule->selection->posts);
    }

    public function testItScansTheSelectionOneBatchAtATime(): void
    {
        $pages = $this->pages(12);
        (new ScanSchedule())->save(new Schedule(Frequency::Daily, new Selection(posts: $pages)));
        $fetcher = new FakePageFetcher(\array_fill_keys(
            \array_map(static fn(int $page): string => (string) \get_permalink($page), $pages),
            '<script src="https://static.hotjar.com/c/hotjar.js"></script>'
        ));
        $sut = $this->makeSut($fetcher);

        $this->assertTrue($sut->start());
        $this->assertTrue($sut->next());
        $this->assertCount(10, $fetcher->fetched);
        $this->assertNull((new ScanReports())->last());

        $this->assertFalse($sut->next());
        $this->assertCount(12, $fetcher->fetched);
        $report = (new ScanReports())->last();
        $this->assertNotNull($report);
        $this->assertSame(12, $report->pages);
        $this->assertSame(['hotjar'], $report->vendors);
        $this->assertSame(['hotjar'], $report->newVendors);
    }

    public function testOnlyWhatThePreviousScanDidNotReportIsNew(): void
    {
        $page = $this->pages(1)[0];
        (new ScanSchedule())->save(new Schedule(Frequency::Daily, new Selection(posts: [$page])));
        $html = '<script src="https://static.hotjar.com/c/hotjar.js"></script>'
            . '<script src="https://cdn.tracker.example.org/t.js"></script>';
        $this->scanAll($this->makeSut(new FakePageFetcher([(string) \get_permalink($page) => $html])));

        $html .= '<iframe src="https://www.youtube.com/embed/a"></iframe>'
            . '<script src="https://widgets.example.net/w.js"></script>';
        \update_option(Schema::OPTION, ['disabled_vendors' => ['hotjar', 'youtube']]);
        $this->scanAll($this->makeSut(new FakePageFetcher([(string) \get_permalink($page) => $html])));

        $report = (new ScanReports())->last();
        $this->assertNotNull($report);
        $this->assertSame(['hotjar', 'youtube'], $report->vendors);
        $this->assertSame(['youtube'], $report->newVendors);
        $this->assertSame(['cdn.tracker.example.org', 'widgets.example.net'], $report->hosts);
        $this->assertSame(['widgets.example.net'], $report->newHosts);
    }

    public function testTheBlockedVendorsNeedNoAction(): void
    {
        $page = $this->pages(1)[0];
        (new ScanSchedule())->save(new Schedule(Frequency::Daily, new Selection(posts: [$page])));
        $html = '<iframe src="https://www.youtube.com/embed/a"></iframe>';

        $this->scanAll($this->makeSut(new FakePageFetcher([(string) \get_permalink($page) => $html])));

        $report = (new ScanReports())->last();
        $this->assertNotNull($report);
        $this->assertSame([], $report->vendors);
        $this->assertFalse($report->hasNews());
    }

    public function testNothingRunsWithoutPagesToScan(): void
    {
        (new ScanSchedule())->save(new Schedule(Frequency::Daily, new Selection()));

        $this->assertFalse($this->makeSut(new FakePageFetcher())->start());
        $this->assertNull((new ScanReports())->last());
    }

    private function scanAll(ScheduledScan $sut): void
    {
        $sut->start();
        while ($sut->next()) {
            // One batch at a time, like the cron events.
        }
    }

    /**
     * @return list<int>
     */
    private function pages(int $count): array
    {
        $pages = [];
        foreach (\range(1, $count) as $number) {
            $page = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
            $this->assertIsInt($page);
            $pages[] = $page;
        }

        return $pages;
    }

    private function makeSut(FakePageFetcher $fetcher): ScheduledScan
    {
        return new ScheduledScan(
            new ScanSchedule(),
            new ScanReports(),
            new ScanTargets(),
            new SiteScanner(
                new ScanTargets(),
                $fetcher,
                new PageAnalyzer(),
                $this->service(VendorsFactory::class)
            ),
            $this->service(VendorSettings::class)
        );
    }
}
