<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Console;

use Psr\Container\ContainerInterface;

/**
 * Builds the classes without dependencies, like the commands of the tests.
 */
final readonly class CommandContainer implements ContainerInterface
{
    public function get(string $id): object
    {
        if (!\class_exists($id)) {
            throw new \InvalidArgumentException($id);
        }

        return new $id();
    }

    public function has(string $id): bool
    {
        return \class_exists($id);
    }
}
