<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Console;

use ItalyCookieChoices\Console\Commands\CommandInterface;
use ItalyCookieChoices\Console\Commands\CommandRegistry;

/**
 * Records the commands and the namespaces in the order they are registered.
 */
final class CommandRegistrySpy implements CommandRegistry
{
    /** @var list<string> */
    public array $names = [];

    public function add(CommandInterface $command): void
    {
        $this->names[] = $command->name();
    }

    public function addNamespace(string $name, string $class): void
    {
        $this->names[] = 'namespace ' . $name . ' ' . $class;
    }
}
