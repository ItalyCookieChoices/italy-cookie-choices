<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\Pipeline;

/**
 * The result of a command, the code it exits with and its message.
 * StatusCode has the common ones, the commands add their own enums.
 */
interface ExitCode
{
    public function code(): int;

    public function message(): string;
}
