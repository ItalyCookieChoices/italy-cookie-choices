<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI\Cli\Commands;

use WP_CLI\Dispatcher\CommandNamespace;

/**
 * Exports and purges the consent log.
 *
 * WP-CLI reads it from its class, it is never built: CommandsConfigExtension registers it by its name.
 */
final class LogNamespace extends CommandNamespace
{
}
