<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\Commands;

/**
 * Where the commands are registered, WP-CLI in ConsoleCommandRegistry.
 */
interface CommandRegistry
{
    public function add(CommandInterface $command): void;

    /**
     * A parent of other commands, like "plugin log", described by the docblock of its class.
     *
     * @param string $class The name of the class, it is not loaded before WP-CLI reads it.
     */
    public function addNamespace(string $name, string $class): void;
}
