<?php

declare(strict_types=1);

namespace ItalyCookieChoices;

use ItalyCookieChoices\I18n\TextDomain;
use ItalyCookieChoices\Legacy\LegacyLoader;
use ItalyCookieChoices\Settings\Registration;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;

/**
 * The plugin services, autowired by the container.
 */
final readonly class Module implements ModuleInterface
{
    /**
     * @param string $legacyPath The legacy folder, with the trailing slash.
     * @param string $languagesPath Relative to the plugins folder, as load_plugin_textdomain() expects it.
     */
    public function __construct(
        private string $legacyPath,
        private string $languagesPath,
    ) {
    }

    public function __invoke(): iterable
    {
        return [
            AurynConfig::ALIASES => [
                ConfigInterface::class => Config::class,
            ],
            AurynConfig::DEFINITIONS => [
                TextDomain::class => [
                    ':languagesPath' => $this->languagesPath,
                ],
                LegacyLoader::class => [
                    ':legacyPath' => $this->legacyPath,
                ],
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                TextDomain::class,
                Registration::class,
                LegacyLoader::class,
            ],
        ];
    }
}
