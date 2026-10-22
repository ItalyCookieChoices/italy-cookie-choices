<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking;

use ItalyCookieChoices\Blocking\UI\BlockingFilters;
use ItalyCookieChoices\Blocking\UI\PageBuffer;
use ItalyCookieChoices\Blocking\UI\Placeholders;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;

/**
 * The blocking context: the vendors, their scripts and frames waiting for the consent, and the placeholders.
 */
final readonly class Module implements ModuleInterface
{
    private const BUILD = 'assets/build/';

    /**
     * @param string $pluginPath The plugin folder, with the trailing slash.
     * @param string $pluginUrl The plugin URL, with the trailing slash.
     */
    public function __construct(
        private string $pluginPath,
        private string $pluginUrl,
    ) {
    }

    public function __invoke(): iterable
    {
        return [
            AurynConfig::DEFINITIONS => [
                Placeholders::class => [
                    ':buildPath' => $this->pluginPath . self::BUILD,
                    ':buildUrl' => $this->pluginUrl . self::BUILD,
                ],
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                BlockingFilters::class,
                PageBuffer::class,
                Placeholders::class,
            ],
        ];
    }
}
