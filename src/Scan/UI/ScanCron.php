<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI;

use ItalyCookieChoices\Scan\Application\ScanReports;
use ItalyCookieChoices\Scan\Application\ScanSchedule;
use ItalyCookieChoices\Scan\Application\ScheduledScan;
use ItalyCookieChoices\Scan\Domain\Frequency;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Cron\GlobalRecurrences;
use ItalyStrap\Cron\Recurrence;
use ItalyStrap\Cron\RecurringEvent;
use ItalyStrap\Cron\SchedulerInterface;
use ItalyStrap\Cron\SingleEvent;
use ItalyStrap\Event\SubscriberInterface;
use Psr\Clock\ClockInterface;

/**
 * The scheduled scan on WP-Cron: the recurring event starts it, a single event scans each batch
 * and schedules the next one, the last batch emails the news when the admin asked for it.
 */
final readonly class ScanCron implements SubscriberInterface
{
    public const RUN = 'italy_cookie_choices_scan';
    public const BATCH = 'italy_cookie_choices_scan_batch';

    /**
     * The first scan runs one hour after the schedule is saved.
     */
    private const FIRST_RUN_DELAY = 3600;

    private RecurringEvent $run;
    private SingleEvent $batch;

    public function __construct(
        private ScheduledScan $scan,
        private ScanSchedule $schedule,
        private ScanReports $reports,
        private ScanMailer $mailer,
        SchedulerInterface $scheduler,
        ClockInterface $clock,
    ) {
        $this->run = new RecurringEvent(self::RUN, $scheduler, $clock, self::FIRST_RUN_DELAY);
        $this->batch = new SingleEvent(self::BATCH, $scheduler, $clock);
    }

    public function getSubscribedEvents(): iterable
    {
        yield GlobalRecurrences::EVENT_NAME => 'addMonthly';
        yield self::RUN => 'start';
        yield self::BATCH => 'next';
        yield Hook::INIT => 'keepScheduled';
    }

    /**
     * The monthly recurrence, built when WP-Cron reads the recurrences: its label is translated after init.
     *
     * @return array<array-key, mixed>
     */
    public function addMonthly(mixed $schedules): array
    {
        $monthly = new Recurrence(
            Frequency::MONTHLY_RECURRENCE,
            Frequency::MONTHLY_INTERVAL,
            \__('Once a month', 'italy-cookie-choices')
        );

        return (new GlobalRecurrences($monthly))->add($schedules);
    }

    public function start(): void
    {
        if (!$this->scan->start()) {
            return;
        }

        $this->batch->schedule();
    }

    public function next(): void
    {
        if ($this->scan->next()) {
            $this->batch->schedule();
            return;
        }

        $report = $this->reports->last();
        if ($report === null || !$report->hasNews() || !$this->schedule->get()->email) {
            return;
        }

        $this->mailer->send($report);
    }

    /**
     * The recurring event of the frequency, none when the scan is off.
     */
    public function reschedule(Frequency $frequency): void
    {
        $this->run->reschedule($frequency->recurrence());
    }

    /**
     * Schedules the event again when it is missing, like after a migration of the site.
     */
    public function keepScheduled(): void
    {
        $this->run->scheduleIfMissing($this->schedule->get()->frequency->recurrence());
    }
}
