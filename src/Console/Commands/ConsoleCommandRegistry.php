<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\Commands;

/**
 * Registers the commands in WP-CLI.
 *
 * WP-CLI keeps a command until its parent namespace is registered, so the order does not matter.
 */
final readonly class ConsoleCommandRegistry implements CommandRegistry
{
    public function add(CommandInterface $command): void
    {
        \WP_CLI::add_command($command->name(), $command);
    }

    /**
     * The class extends WP_CLI\Dispatcher\CommandNamespace, WP-CLI reads it without building it.
     */
    public function addNamespace(string $name, string $class): void
    {
        \WP_CLI::add_command($name, $class);
    }
}
