<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure;

use ItalyCookieChoices\Migration\Application\BuildingMigrationSources;
use ItalyCookieChoices\Migration\Infrastructure\Complianz\ComplianzSource;
use ItalyCookieChoices\Migration\Infrastructure\CookieYes\CookieYesSource;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Adds the sources of the plugin, the same way other plugins add theirs.
 */
final readonly class BuiltInSources implements SubscriberInterface
{
    public function __construct(
        private ComplianzSource $complianz,
        private CookieYesSource $cookieYes,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield BuildingMigrationSources::class => 'add';
    }

    public function add(BuildingMigrationSources $event): void
    {
        $event->addSource($this->complianz);
        $event->addSource($this->cookieYes);
    }
}
