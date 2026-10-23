<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI;

use ItalyCookieChoices\ConsentLog\Infrastructure\LogTable;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Creates or upgrades the table of the log, also after an update that skipped the activation.
 */
final readonly class LogTableInstaller implements SubscriberInterface
{
    public function __construct(
        private LogTable $table,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'plugins_loaded' => 'install';
    }

    public function install(): void
    {
        if ($this->table->isCurrent()) {
            return;
        }

        $this->table->install();
    }
}
