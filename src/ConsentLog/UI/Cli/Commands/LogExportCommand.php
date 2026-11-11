<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI\Cli\Commands;

use ItalyCookieChoices\Console\Commands\CommandInterface;
use ItalyCookieChoices\Console\IO\IOFactory;
use ItalyCookieChoices\Console\Middlewares\OpenOutput;
use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\ConsentLog\UI\Cli\Handlers\WriteConsentLog;

/**
 * wp cookie-choices log export
 */
final readonly class LogExportCommand implements CommandInterface
{
    public function __construct(
        private CommandRunner $runner,
        private IOFactory $io,
        private OpenOutput $output,
        private WriteConsentLog $write,
    ) {
    }

    public function name(): string
    {
        return 'cookie-choices log export';
    }

    /**
     * Exports the consent log as CSV, the same file of the Consent log tab of the settings page.
     *
     * ## OPTIONS
     *
     * [--file=<path>]
     * : The file to write, the standard output without it.
     *
     * ## EXAMPLES
     *
     *     wp cookie-choices log export --file=consent-log.csv
     *
     * @param array<array-key, mixed> $arguments
     * @param array<array-key, mixed> $options
     */
    public function __invoke(array $arguments, array $options): void
    {
        $this->runner->run(new Message($this->io->create($arguments, $options)), $this->write, $this->output);
    }
}
