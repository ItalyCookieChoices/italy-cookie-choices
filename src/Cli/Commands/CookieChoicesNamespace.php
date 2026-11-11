<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cli\Commands;

use WP_CLI\Dispatcher\CommandNamespace;

/**
 * Manages Cookie Choices: the status, the consent log and the settings.
 *
 * WP-CLI reads it from its class, it is never built: CommandsConfigExtension registers it by its name.
 */
final class CookieChoicesNamespace extends CommandNamespace
{
}
