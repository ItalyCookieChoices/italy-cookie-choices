<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\IO;

/**
 * Builds the terminal of a command from the arguments WP-CLI passes to it.
 */
interface IOFactory
{
    /**
     * @param array<array-key, mixed> $arguments
     * @param array<array-key, mixed> $options
     */
    public function create(array $arguments, array $options): IOInterface;
}
