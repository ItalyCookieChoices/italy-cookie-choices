<?php

declare(strict_types=1);

namespace ItalyCookieChoices;

/**
 * Dispatched once the plugin is loaded and its services are subscribed, before the 2.x icc_plugin_loaded action:
 *
 * add_action( PluginLoaded::class, static function ( PluginLoaded $event ): void {
 *     // Listen to the plugin.
 * } );
 */
final readonly class PluginLoaded
{
}
