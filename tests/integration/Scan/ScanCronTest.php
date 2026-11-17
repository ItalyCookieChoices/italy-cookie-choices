<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\Application\ScanReports;
use ItalyCookieChoices\Scan\Application\ScanSchedule;
use ItalyCookieChoices\Scan\Domain\Frequency;
use ItalyCookieChoices\Scan\Domain\Report;
use ItalyCookieChoices\Scan\Domain\Schedule;
use ItalyCookieChoices\Scan\Domain\Selection;
use ItalyCookieChoices\Scan\UI\ScanCron;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScanCronTest extends WPTestCase
{
    use BuildsContainer;

    /** @var list<array{to: string, subject: string, message: string}> */
    private array $mails = [];

    public function setUp(): void
    {
        parent::setUp();
        \add_filter('pre_wp_mail', function (mixed $sent, array $mail): bool {
            $this->mails[] = [
                'to' => \is_string($mail['to'] ?? null) ? $mail['to'] : '',
                'subject' => \is_string($mail['subject'] ?? null) ? $mail['subject'] : '',
                'message' => \is_string($mail['message'] ?? null) ? $mail['message'] : '',
            ];

            return true;
        }, 10, 2);
        // The pages of the site, read without a real loopback request.
        \add_filter('pre_http_request', static fn(): array => [
            'response' => ['code' => 200, 'message' => 'OK'],
            'body' => '<script src="https://static.hotjar.com/c/hotjar.js"></script>',
            'headers' => [],
            'cookies' => [],
        ]);
    }

    public function tearDown(): void
    {
        \wp_clear_scheduled_hook(ScanCron::RUN);
        \wp_clear_scheduled_hook(ScanCron::BATCH);
        parent::tearDown();
    }

    public function testItListensToItsCronEvents(): void
    {
        $this->assertSame(
            [
                'cron_schedules' => 'addMonthly',
                ScanCron::RUN => 'start',
                ScanCron::BATCH => 'next',
                'init' => 'keepScheduled',
            ],
            \iterator_to_array($this->sut()->getSubscribedEvents())
        );
    }

    public function testItAddsTheMonthlyRecurrence(): void
    {
        $schedules = $this->sut()->addMonthly(['daily' => ['interval' => \DAY_IN_SECONDS, 'display' => 'Once Daily']]);

        $monthly = $schedules[Frequency::MONTHLY_RECURRENCE] ?? null;
        $this->assertIsArray($monthly);
        $this->assertSame(Frequency::MONTHLY_INTERVAL, $monthly['interval'] ?? null);
        $this->assertArrayHasKey('daily', $schedules);
    }

    public function testTheScheduleSetsTheRecurringEvent(): void
    {
        $sut = $this->sut();

        $sut->reschedule(Frequency::Weekly);
        $this->assertSame('weekly', \wp_get_schedule(ScanCron::RUN));

        $sut->reschedule(Frequency::Off);
        $this->assertFalse(\wp_next_scheduled(ScanCron::RUN));
    }

    public function testTheEventIsScheduledAgainWhenItIsMissing(): void
    {
        (new ScanSchedule())->save(new Schedule(Frequency::Daily, new Selection(home: true)));

        $this->sut()->keepScheduled();

        $this->assertSame('daily', \wp_get_schedule(ScanCron::RUN));
    }

    public function testTheScanRunsInBatchesAndEmailsTheNews(): void
    {
        \update_option('admin_email', 'owner@example.com');
        \update_option('italy_cookie_choices', ['disabled_vendors' => ['hotjar']]);
        (new ScanSchedule())->save(new Schedule(Frequency::Daily, new Selection(home: true), true));
        $sut = $this->sut();

        $sut->start();
        $this->assertNotFalse(\wp_next_scheduled(ScanCron::BATCH));
        \wp_clear_scheduled_hook(ScanCron::BATCH);
        $sut->next();

        $this->assertFalse(\wp_next_scheduled(ScanCron::BATCH));
        $mails = $this->scanMails();
        $this->assertCount(1, $mails);
        $this->assertSame('owner@example.com', $mails[0]['to']);
        $this->assertStringContainsString('Hotjar', $mails[0]['message']);
    }

    public function testNoEmailWithoutNewsOrWithoutTheChoiceOfTheAdmin(): void
    {
        \update_option('italy_cookie_choices', ['disabled_vendors' => ['hotjar']]);
        (new ScanSchedule())->save(new Schedule(Frequency::Daily, new Selection(home: true), false));
        $sut = $this->sut();

        $sut->start();
        $sut->next();
        $this->assertSame([], $this->scanMails());

        (new ScanSchedule())->save(new Schedule(Frequency::Daily, new Selection(home: true), true));
        $sut->start();
        $sut->next();

        $report = (new ScanReports())->last();
        $this->assertInstanceOf(Report::class, $report);
        $this->assertFalse($report->hasNews());
        $this->assertSame([], $this->scanMails());
    }

    /**
     * The emails of the scan, without the ones of WordPress, like the change of the admin address.
     *
     * @return list<array{to: string, subject: string, message: string}>
     */
    private function scanMails(): array
    {
        return \array_values(\array_filter(
            $this->mails,
            static fn(array $mail): bool => \str_contains($mail['subject'], 'found new vendors')
        ));
    }

    private function sut(): ScanCron
    {
        return $this->service(ScanCron::class);
    }
}
