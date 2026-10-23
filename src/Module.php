<?php

declare(strict_types=1);

namespace ItalyCookieChoices;

use ItalyCookieChoices\I18n\TextDomain;
use ItalyCookieChoices\Legacy\LegacyLoader;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;
use ItalyStrap\Finder\FinderFactory;
use ItalyStrap\View\View;
use ItalyStrap\View\ViewInterface;

/**
 * The plugin services, autowired by the container.
 */
final readonly class Module implements ModuleInterface
{
    /**
     * @param string $legacyPath The legacy folder, with the trailing slash.
     * @param string $languagesPath Relative to the plugins folder, as load_plugin_textdomain() expects it.
     * @param string $templatesPath The templates folder, with the trailing slash.
     */
    public function __construct(
        private string $legacyPath,
        private string $languagesPath,
        private string $templatesPath,
    ) {
    }

    public function __invoke(): iterable
    {
        return [
            AurynConfig::ALIASES => [
                ConfigInterface::class => Config::class,
            ],
            AurynConfig::DELEGATIONS => [
                ViewInterface::class => fn(): ViewInterface => $this->view(),
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
                LegacyLoader::class,
            ],
        ];
    }

    /**
     * The templates of the plugin, rendered with the data as a Config object.
     */
    private function view(): ViewInterface
    {
        $finder = (new FinderFactory())->make();
        $finder->in($this->templatesPath);

        return new View($finder);
    }
}
