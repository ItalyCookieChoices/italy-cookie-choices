<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan;

use ItalyCookieChoices\Console\Commands\CommandsConfigExtension;
use ItalyCookieChoices\Scan\Application\PageFetcher;
use ItalyCookieChoices\Scan\Infrastructure\LoopbackFetcher;
use ItalyCookieChoices\Scan\UI\Cli\Commands\ScanCommand;
use ItalyCookieChoices\Scan\UI\ScanCron;
use ItalyCookieChoices\Scan\UI\ScanEndpoint;
use ItalyCookieChoices\Scan\UI\ScanNotice;
use ItalyCookieChoices\Scan\UI\ScheduleEndpoint;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;

/**
 * The scan context: it reads the pages of the site and finds the vendors and the unknown third parties,
 * from the settings page and from WP-CLI.
 */
final readonly class Module implements ModuleInterface
{
    public function __invoke(): iterable
    {
        return [
            AurynConfig::ALIASES => [
                PageFetcher::class => LoopbackFetcher::class,
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                ScanEndpoint::class,
                ScanCron::class,
                ScheduleEndpoint::class,
                ScanNotice::class,
            ],
            CommandsConfigExtension::COMMANDS => [
                ScanCommand::class,
            ],
        ];
    }
}
