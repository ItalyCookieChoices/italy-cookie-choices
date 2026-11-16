<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI\Cli;

use ItalyCookieChoices\Console\Pipeline\ExitCode;

/**
 * The exit codes of the scan command, after the ones of the settings and of the log.
 */
enum ScanExitCode: int implements ExitCode
{
    case NothingToScan = 7;

    public function code(): int
    {
        return $this->value; // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts -- An enum case.
    }

    public function message(): string
    {
        return \__('There are no published pages to scan in the selection.', 'italy-cookie-choices');
    }
}
