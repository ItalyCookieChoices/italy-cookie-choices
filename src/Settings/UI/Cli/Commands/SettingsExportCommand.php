<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI\Cli\Commands;

use ItalyCookieChoices\Console\Commands\CommandInterface;
use ItalyCookieChoices\Console\IO\IOFactory;
use ItalyCookieChoices\Console\Middlewares\OpenOutput;
use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Settings\UI\Cli\Handlers\WriteSettingsFile;

/**
 * wp cookie-choices settings export
 */
final readonly class SettingsExportCommand implements CommandInterface
{
    public function __construct(
        private CommandRunner $runner,
        private IOFactory $io,
        private OpenOutput $output,
        private WriteSettingsFile $write,
    ) {
    }

    public function name(): string
    {
        return 'cookie-choices settings export';
    }

    /**
     * Exports the settings as JSON, the file of the Tools tab of the settings page.
     *
     * ## OPTIONS
     *
     * [--file=<path>]
     * : The file to write, the standard output without it.
     *
     * ## EXAMPLES
     *
     *     wp cookie-choices settings export --file=cookie-choices.json
     *     wp cookie-choices settings export > cookie-choices.json
     *
     * @param array<array-key, mixed> $arguments
     * @param array<array-key, mixed> $options
     */
    public function __invoke(array $arguments, array $options): void
    {
        $this->runner->run(new Message($this->io->create($arguments, $options)), $this->write, $this->output);
    }
}
