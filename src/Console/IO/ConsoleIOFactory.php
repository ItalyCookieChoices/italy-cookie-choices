<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\IO;

final readonly class ConsoleIOFactory implements IOFactory
{
    public function create(array $arguments, array $options): IOInterface
    {
        return new ConsoleIO($arguments, $options);
    }
}
