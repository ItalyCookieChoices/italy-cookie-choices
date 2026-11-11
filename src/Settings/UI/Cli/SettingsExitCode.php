<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI\Cli;

use ItalyCookieChoices\Console\Pipeline\ExitCode;

/**
 * The exit codes of the settings commands, after the common ones of StatusCode.
 */
enum SettingsExitCode: int implements ExitCode
{
    case InvalidSettingsFile = 5;

    public function code(): int
    {
        return $this->value; // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts -- An enum case.
    }

    public function message(): string
    {
        return \__('The file has no settings of Cookie Choices.', 'italy-cookie-choices');
    }
}
