<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI\Cli\Handlers;

use ItalyCookieChoices\Console\Pipeline\ExitCode;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Console\Pipeline\StatusCode;
use ItalyCookieChoices\Settings\SettingsFile;
use ItalyCookieChoices\Settings\UI\Cli\Middlewares\ReadSettingsFile;
use ItalyCookieChoices\Settings\UI\Cli\SettingsExitCode;
use ItalyStrap\Pipeline\HandlerInterface;

/**
 * Saves the settings of the file read by ReadSettingsFile, after the confirmation.
 */
final readonly class ImportSettings implements HandlerInterface
{
    public function __construct(
        private SettingsFile $file,
    ) {
    }

    public function handle(object $message): ExitCode
    {
        \assert($message instanceof Message);
        $io = $message->io();
        $question = \__('Replace the settings of Cookie Choices with the ones of the file?', 'italy-cookie-choices');
        if (!$io->confirm($question)) {
            return StatusCode::Cancelled;
        }

        $file = $message->get(ReadSettingsFile::FILE);

        try {
            $this->file->import(\is_array($file) ? $file : []);
        } catch (\InvalidArgumentException) {
            return SettingsExitCode::InvalidSettingsFile;
        }

        $io->success(\__('The settings are imported.', 'italy-cookie-choices'));

        return StatusCode::Success;
    }
}
