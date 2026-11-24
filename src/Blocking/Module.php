<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking;

use ItalyCookieChoices\Blocking\UI\BlockingFilters;
use ItalyCookieChoices\Blocking\UI\CustomScriptsPrinter;
use ItalyCookieChoices\Blocking\UI\PageBuffer;
use ItalyCookieChoices\Blocking\UI\Placeholders;
use ItalyCookieChoices\Blocking\UI\VendorAbilities;
use ItalyCookieChoices\Module as PluginModule;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;

/**
 * The blocking context: the vendors, their scripts and frames waiting for the consent, the placeholders
 * and the custom scripts of the site owner.
 */
final readonly class Module implements ModuleInterface
{
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
                    ':buildPath' => $this->pluginPath . PluginModule::BUILD,
                    ':buildUrl' => $this->pluginUrl . PluginModule::BUILD,
                ],
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                BlockingFilters::class,
                CustomScriptsPrinter::class,
                PageBuffer::class,
                Placeholders::class,
                VendorAbilities::class,
            ],
        ];
    }
}
