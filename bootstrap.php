<?php

/**
 * The composition root: the container autowires the plugin services and hooks them into WordPress.
 */

declare(strict_types=1);

namespace ItalyCookieChoices;

use ItalyStrap\Empress\ContainerBuilder;
use ItalyStrap\Event\Module as EventModule;
use ItalyStrap\Event\SubscribersConfigExtension;
use Psr\EventDispatcher\EventDispatcherInterface;

// A closure keeps the variables out of the global scope
(static function (string $pluginPath, string $pluginUrl, string $basename): void {
    $container = (new ContainerBuilder())
        ->addModule(new EventModule())
        ->addModule(new Module(
            \dirname($basename) . '/languages',
            $pluginPath . 'templates/'
        ))
        ->addModule(new Settings\Module($pluginPath, $pluginUrl, $basename))
        ->addModule(new Consent\Module($pluginPath, $pluginUrl, $_COOKIE))
        ->addModule(new Blocking\Module($pluginPath, $pluginUrl))
        ->addModule(new ConsentLog\Module($pluginPath, $pluginUrl))
        ->addModule(new Integrations\Module($pluginPath))
        ->extend(SubscribersConfigExtension::class)
        ->build();

    $dispatcher = $container->get(EventDispatcherInterface::class);
    \assert($dispatcher instanceof EventDispatcherInterface);
    $dispatcher->dispatch(new PluginLoaded());

    /**
     * Fires once the plugin is loaded, listen to PluginLoaded instead.
     *
     * @since 2.5.0
     */
    \do_action('icc_plugin_loaded');
})(\plugin_dir_path(__FILE__), \plugin_dir_url(__FILE__), \plugin_basename(__DIR__ . '/italy-cookie-choices.php'));
