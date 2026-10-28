<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

use ItalyCookieChoices\Settings\UI\ImportEndpoint;
use ItalyCookieChoices\Settings\UI\PluginActionLinks;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyCookieChoices\Settings\UI\StringRegistration;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;

/**
 * The settings context: the option, its migration, its translations and the settings page.
 */
final readonly class Module implements ModuleInterface
{
    private const BUILD = 'assets/build/';

    /**
     * @param string $pluginPath The plugin folder, with the trailing slash.
     * @param string $pluginUrl The plugin URL, with the trailing slash.
     * @param string $basename The basename of the main plugin file.
     */
    public function __construct(
        private string $pluginPath,
        private string $pluginUrl,
        private string $basename,
    ) {
    }

    public function __invoke(): iterable
    {
        return [
            AurynConfig::DEFINITIONS => [
                SettingsPage::class => [
                    ':buildPath' => $this->pluginPath . self::BUILD,
                    ':buildUrl' => $this->pluginUrl . self::BUILD,
                    ':languagesPath' => $this->pluginPath . 'languages',
                ],
                PluginActionLinks::class => [
                    ':basename' => $this->basename,
                ],
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                Migration::class,
                Registration::class,
                StringRegistration::class,
                SettingsPage::class,
                ImportEndpoint::class,
                PluginActionLinks::class,
            ],
        ];
    }
}
