<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support;

use ItalyCookieChoices\Blocking\Module as BlockingModule;
use ItalyCookieChoices\Consent\Module as ConsentModule;
use ItalyCookieChoices\ConsentLog\Module as ConsentLogModule;
use ItalyCookieChoices\Integrations\Module as IntegrationsModule;
use ItalyCookieChoices\Module;
use ItalyCookieChoices\Settings\Module as SettingsModule;
use ItalyStrap\Empress\ContainerBuilder;
use ItalyStrap\Event\Module as EventModule;
use Psr\Container\ContainerInterface;

/**
 * The container with the modules of the plugin, without the subscribers hooked.
 */
trait BuildsContainer
{
    private function container(): ContainerInterface
    {
        $pluginPath = \dirname(__DIR__, 2) . '/';
        $pluginUrl = 'https://example.com/wp-content/plugins/italy-cookie-choices/';

        return (new ContainerBuilder())
            ->addModule(new EventModule())
            ->addModule(new Module(
                $pluginPath . 'legacy/',
                'italy-cookie-choices/languages',
                $pluginPath . 'templates/'
            ))
            ->addModule(new SettingsModule($pluginPath, $pluginUrl, 'italy-cookie-choices/italy-cookie-choices.php'))
            ->addModule(new ConsentModule($pluginPath, $pluginUrl, []))
            ->addModule(new BlockingModule($pluginPath, $pluginUrl))
            ->addModule(new ConsentLogModule($pluginPath, $pluginUrl))
            ->addModule(new IntegrationsModule($pluginPath))
            ->build();
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function service(string $class): object
    {
        $service = $this->container()->get($class);
        \assert($service instanceof $class);

        return $service;
    }
}
