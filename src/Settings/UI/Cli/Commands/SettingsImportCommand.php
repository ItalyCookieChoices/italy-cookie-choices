<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI\Cli\Commands;

use ItalyCookieChoices\Console\Commands\CommandInterface;
use ItalyCookieChoices\Console\IO\IOFactory;
use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Settings\UI\Cli\Handlers\ImportSettings;
use ItalyCookieChoices\Settings\UI\Cli\Middlewares\ReadSettingsFile;

/**
 * wp cookie-choices settings import <file>
 */
final readonly class SettingsImportCommand implements CommandInterface
{
    public function __construct(
        private CommandRunner $runner,
        private IOFactory $io,
        private ReadSettingsFile $read,
        private ImportSettings $import,
    ) {
    }

    public function name(): string
    {
        return 'cookie-choices settings import';
    }

    /**
     * Imports the settings of a JSON file, exported by the settings page, by this command or by the 2.x versions.
     *
     * The settings replace the current ones, they are migrated from the version of the file.
     *
     * ## OPTIONS
     *
     * <file>
     * : The JSON file.
     *
     * [--yes]
     * : Replace the settings without asking.
     *
     * ## EXAMPLES
     *
     *     wp cookie-choices settings import cookie-choices.json --yes
     *
     * @param array<array-key, mixed> $arguments
     * @param array<array-key, mixed> $options
     */
    public function __invoke(array $arguments, array $options): void
    {
        $this->runner->run(new Message($this->io->create($arguments, $options)), $this->import, $this->read);
    }
}
