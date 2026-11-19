<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\UI\Cli;

use ItalyCookieChoices\Console\Pipeline\ExitCode;

/**
 * The exit codes of the migrate command, after the ones of the scan.
 */
enum MigrationExitCode: int implements ExitCode
{
    case UnknownSource = 8;

    public function code(): int
    {
        return $this->value; // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts -- An enum case.
    }

    public function message(): string
    {
        return \__('There are no settings of this plugin to import.', 'italy-cookie-choices');
    }
}
