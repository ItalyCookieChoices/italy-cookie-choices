<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI\Cli\Commands;

use WP_CLI\Dispatcher\CommandNamespace;

/**
 * Exports and imports the settings, in the file of the settings page.
 *
 * WP-CLI reads it from its class, it is never built: CommandsConfigExtension registers it by its name.
 */
final class SettingsNamespace extends CommandNamespace
{
}
