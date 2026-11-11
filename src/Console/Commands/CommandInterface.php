<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\Commands;

/**
 * A command of WP-CLI, a class with __invoke(), with its name, like "plugin log export".
 */
interface CommandInterface
{
    public function name(): string;
}
