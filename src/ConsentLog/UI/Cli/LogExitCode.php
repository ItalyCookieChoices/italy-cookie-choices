<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI\Cli;

use ItalyCookieChoices\Console\Pipeline\ExitCode;

/**
 * The exit codes of the log commands, after the common ones of StatusCode and the settings ones.
 */
enum LogExitCode: int implements ExitCode
{
    case InvalidDate = 6;

    public function code(): int
    {
        return $this->value; // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts -- An enum case.
    }

    public function message(): string
    {
        return \__('The date is not valid, use the format YYYY-MM-DD.', 'italy-cookie-choices');
    }
}
