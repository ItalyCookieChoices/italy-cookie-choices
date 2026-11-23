<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Dashboard;

use ItalyCookieChoices\Dashboard\UI\DashboardWidget;
use ItalyCookieChoices\Dashboard\UI\StatusAbility;
use ItalyCookieChoices\Module as PluginModule;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;

/**
 * The dashboard context: the widget with the status of the settings and the consents of the month.
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
                DashboardWidget::class => [
                    ':buildPath' => $this->pluginPath . PluginModule::BUILD,
                    ':buildUrl' => $this->pluginUrl . PluginModule::BUILD,
                ],
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                DashboardWidget::class,
                StatusAbility::class,
            ],
        ];
    }
}
