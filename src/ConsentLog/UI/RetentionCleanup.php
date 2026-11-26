<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI;

use ItalyCookieChoices\ConsentLog\Application\LogSettings;
use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Cron\RecurringEvent;
use ItalyStrap\Cron\SchedulerInterface;
use ItalyStrap\Event\SubscriberInterface;
use Psr\Clock\ClockInterface;

/**
 * Deletes every day the records older than the retention.
 */
final readonly class RetentionCleanup implements SubscriberInterface
{
    public const EVENT_NAME = 'italy_cookie_choices_consent_log_cleanup';
    private const RECURRENCE = 'daily';

    private RecurringEvent $event;

    public function __construct(
        private ConsentLog $log,
        private LogSettings $settings,
        SchedulerInterface $scheduler,
        private ClockInterface $clock,
    ) {
        $this->event = new RecurringEvent(self::EVENT_NAME, $scheduler, $clock);
    }

    public function getSubscribedEvents(): iterable
    {
        yield Hook::INIT => 'schedule';
        yield self::EVENT_NAME => 'cleanup';
    }

    /**
     * Schedules the cleanup when the log is on, it is not stopped when the log is turned off.
     */
    public function schedule(): void
    {
        $this->event->scheduleIfMissing($this->settings->isEnabled() ? self::RECURRENCE : RecurringEvent::OFF);
    }

    /**
     * The old records are deleted even when the log is turned off, they are kept only as long as needed.
     */
    public function cleanup(): int
    {
        return $this->log->deleteBefore($this->settings->retention()->cutoff($this->clock->now()));
    }
}
