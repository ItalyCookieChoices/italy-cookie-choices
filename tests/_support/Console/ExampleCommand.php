<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Console;

use ItalyCookieChoices\Console\Commands\CommandInterface;

final readonly class ExampleCommand implements CommandInterface
{
    public function name(): string
    {
        return 'example run';
    }

    public function __invoke(): void
    {
    }
}
