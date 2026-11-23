<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan;

use ItalyCookieChoices\Console\Commands\CommandsConfigExtension;
use ItalyCookieChoices\Scan\Application\PageFetcher;
use ItalyCookieChoices\Scan\Infrastructure\LoopbackFetcher;
use ItalyCookieChoices\Scan\UI\Cli\Commands\ScanCommand;
use ItalyCookieChoices\Scan\UI\CookieRecorder;
use ItalyCookieChoices\Scan\UI\FrameScanEndpoint;
use ItalyCookieChoices\Scan\UI\ScanAbilities;
use ItalyCookieChoices\Scan\UI\ScanCron;
use ItalyCookieChoices\Scan\UI\ScanEndpoint;
use ItalyCookieChoices\Scan\UI\ScanNotice;
use ItalyCookieChoices\Scan\UI\ScanRequest;
use ItalyCookieChoices\Scan\UI\ScheduleEndpoint;
use ItalyStrap\Cache\Expiration;
use ItalyStrap\Cache\SimpleCache;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;
use ItalyStrap\Storage\Transient;
use Psr\SimpleCache\CacheInterface;

/**
 * The scan context: it reads the pages of the site and finds the vendors and the unknown third parties,
 * from the settings page and from WP-CLI, and the cookies the pages set in the browser.
 */
final readonly class Module implements ModuleInterface
{
    private const BUILD = 'assets/build/';

    /**
     * @param string $pluginPath The plugin folder, with the trailing slash.
     * @param string $pluginUrl The plugin URL, with the trailing slash.
     * @param array<array-key, mixed> $query The query of the request.
     */
    public function __construct(
        private string $pluginPath,
        private string $pluginUrl,
        private array $query,
    ) {
    }

    public function __invoke(): iterable
    {
        return [
            AurynConfig::SHARING => [
                ScanRequest::class,
            ],
            AurynConfig::ALIASES => [
                PageFetcher::class => LoopbackFetcher::class,
            ],
            AurynConfig::DELEGATIONS => [
                // The PSR-16 cache of the scan tokens, kept in the transients.
                CacheInterface::class => static fn(): CacheInterface => new SimpleCache(
                    new Transient(),
                    new Expiration()
                ),
            ],
            AurynConfig::DEFINITIONS => [
                ScanRequest::class => [
                    ':query' => $this->query,
                ],
                CookieRecorder::class => [
                    ':buildPath' => $this->pluginPath . self::BUILD,
                    ':buildUrl' => $this->pluginUrl . self::BUILD,
                ],
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                ScanEndpoint::class,
                ScanCron::class,
                ScheduleEndpoint::class,
                ScanNotice::class,
                ScanRequest::class,
                FrameScanEndpoint::class,
                CookieRecorder::class,
                ScanAbilities::class,
            ],
            CommandsConfigExtension::COMMANDS => [
                ScanCommand::class,
            ],
        ];
    }
}
