<?php

declare(strict_types=1);

namespace ItalyCookieChoices;

use ItalyCookieChoices\I18n\TextDomain;
use ItalyCookieChoices\Legacy\DeprecatedEventNames;
use ItalyStrap\Asset\AssetLoader;
use ItalyStrap\Asset\Core\CoreScriptRegistry;
use ItalyStrap\Asset\Core\CoreStyleRegistry;
use ItalyStrap\Asset\ScriptRegistry;
use ItalyStrap\Asset\StyleRegistry;
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
     * @param string $languagesPath Relative to the plugins folder, as load_plugin_textdomain() expects it.
     * @param string $templatesPath The templates folder, with the trailing slash.
     */
    public function __construct(
        private string $languagesPath,
        private string $templatesPath,
    ) {
    }

    public function __invoke(): iterable
    {
        return [
            AurynConfig::SHARING => [
                AssetLoader::class,
            ],
            AurynConfig::ALIASES => [
                ConfigInterface::class => Config::class,
                // The scripts and the styles of the UI classes, registered in the core
                ScriptRegistry::class => CoreScriptRegistry::class,
                StyleRegistry::class => CoreStyleRegistry::class,
            ],
            AurynConfig::DELEGATIONS => [
                ViewInterface::class => fn(): ViewInterface => $this->view(),
            ],
            AurynConfig::DEFINITIONS => [
                TextDomain::class => [
                    ':languagesPath' => $this->languagesPath,
                ],
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                TextDomain::class,
                DeprecatedEventNames::class,
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
