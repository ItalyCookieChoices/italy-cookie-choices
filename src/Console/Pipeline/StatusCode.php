<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\Pipeline;

/**
 * The common exit codes, 0 when the command succeeds.
 */
enum StatusCode: int implements ExitCode
{
    case Success = 0;
    case Failure = 1;
    case Cancelled = 2;
    case FileNotWritable = 3;
    case FileNotReadable = 4;

    public function code(): int
    {
        return $this->value; // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts -- An enum case.
    }

    public function message(): string
    {
        // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts -- An enum case.
        return match ($this) {
            self::Success => \__('Done.', 'italy-cookie-choices'),
            self::Failure => \__('The command failed.', 'italy-cookie-choices'),
            self::Cancelled => \__('Nothing changed.', 'italy-cookie-choices'),
            self::FileNotWritable => \__('The file cannot be written.', 'italy-cookie-choices'),
            self::FileNotReadable => \__('The file cannot be read.', 'italy-cookie-choices'),
        };
    }
}
