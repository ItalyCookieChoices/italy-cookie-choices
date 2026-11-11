<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI\Cli\Commands;

use ItalyCookieChoices\Console\Commands\CommandInterface;
use ItalyCookieChoices\Console\IO\IOFactory;
use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\ConsentLog\UI\Cli\Handlers\PurgeLog;
use ItalyCookieChoices\ConsentLog\UI\Cli\Middlewares\ResolvePurgeDate;

/**
 * wp cookie-choices log purge
 */
final readonly class LogPurgeCommand implements CommandInterface
{
    public function __construct(
        private CommandRunner $runner,
        private IOFactory $io,
        private ResolvePurgeDate $date,
        private PurgeLog $purge,
    ) {
    }

    public function name(): string
    {
        return 'cookie-choices log purge';
    }

    /**
     * Deletes the records of the consent log, the ones older than the retention by default.
     *
     * The records demonstrate the consents: export the log before deleting them.
     *
     * ## OPTIONS
     *
     * [--before=<date>]
     * : Delete the records before this date, YYYY-MM-DD in the timezone of the site.
     *
     * [--all]
     * : Delete all the records.
     *
     * [--yes]
     * : Delete without asking.
     *
     * ## EXAMPLES
     *
     *     wp cookie-choices log purge
     *     wp cookie-choices log purge --before=2026-01-01 --yes
     *
     * @param array<array-key, mixed> $arguments
     * @param array<array-key, mixed> $options
     */
    public function __invoke(array $arguments, array $options): void
    {
        $this->runner->run(new Message($this->io->create($arguments, $options)), $this->purge, $this->date);
    }
}
