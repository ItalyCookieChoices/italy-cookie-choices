<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI;

use ItalyCookieChoices\Scan\Application\ScanReports;
use ItalyCookieChoices\Scan\Application\ScanSchedule;
use ItalyCookieChoices\Scan\Application\ScheduledScan;
use ItalyCookieChoices\Scan\Domain\Frequency;
use ItalyStrap\Event\SubscriberInterface;

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

    public function __construct(
        private ScheduledScan $scan,
        private ScanSchedule $schedule,
        private ScanReports $reports,
        private ScanMailer $mailer,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'cron_schedules' => 'addMonthly';
        yield self::RUN => 'start';
        yield self::BATCH => 'next';
        yield 'init' => 'keepScheduled';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function addMonthly(mixed $schedules): array
    {
        return [
            ...(\is_array($schedules) ? $schedules : []),
            Frequency::MONTHLY_RECURRENCE => [
                'interval' => Frequency::MONTHLY_INTERVAL,
                'display' => \__('Once a month', 'italy-cookie-choices'),
            ],
        ];
    }

    public function start(): void
    {
        if (!$this->scan->start()) {
            return;
        }

        \wp_schedule_single_event(\time(), self::BATCH);
    }

    public function next(): void
    {
        if ($this->scan->next()) {
            \wp_schedule_single_event(\time(), self::BATCH);
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
        \wp_clear_scheduled_hook(self::RUN);
        if ($frequency->recurrence() === '') {
            return;
        }

        \wp_schedule_event(\time() + self::FIRST_RUN_DELAY, $frequency->recurrence(), self::RUN);
    }

    /**
     * Schedules the event again when it is missing, like after a migration of the site.
     */
    public function keepScheduled(): void
    {
        $frequency = $this->schedule->get()->frequency;
        if ($frequency === Frequency::Off || \wp_next_scheduled(self::RUN) !== false) {
            return;
        }

        $this->reschedule($frequency);
    }
}
