<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI;

use ItalyCookieChoices\ConsentLog\Application\LogSettings;
use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Deletes every day the records older than the retention.
 */
final readonly class RetentionCleanup implements SubscriberInterface
{
    public const HOOK = 'italy_cookie_choices_consent_log_cleanup';

    public function __construct(
        private ConsentLog $log,
        private LogSettings $settings,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'init' => 'schedule';
        yield self::HOOK => 'cleanup';
    }

    public function schedule(): void
    {
        $isScheduled = \wp_next_scheduled(self::HOOK) !== false;
        if ($isScheduled || !$this->settings->isEnabled()) {
            return;
        }

        \wp_schedule_event(\time(), 'daily', self::HOOK);
    }

    /**
     * The old records are deleted even when the log is turned off, they are kept only as long as needed.
     */
    public function cleanup(): int
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return $this->log->deleteBefore($this->settings->retention()->cutoff($now));
    }
}
