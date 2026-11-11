<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cli\Commands;

use ItalyCookieChoices\Cli\Handlers\ShowStatus;
use ItalyCookieChoices\Console\Commands\CommandInterface;
use ItalyCookieChoices\Console\IO\IOFactory;
use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\Console\Pipeline\Message;

/**
 * wp cookie-choices status
 */
final readonly class StatusCommand implements CommandInterface
{
    public function __construct(
        private CommandRunner $runner,
        private IOFactory $io,
        private ShowStatus $status,
    ) {
    }

    public function name(): string
    {
        return 'cookie-choices status';
    }

    /**
     * Shows the banner, the blocked vendors, the integrations and the consent log of the site.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : The format of the list.
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - yaml
     *   - csv
     * ---
     *
     * ## EXAMPLES
     *
     *     wp cookie-choices status
     *     wp cookie-choices status --format=json
     *
     * @param array<array-key, mixed> $arguments
     * @param array<array-key, mixed> $options
     */
    public function __invoke(array $arguments, array $options): void
    {
        $this->runner->run(new Message($this->io->create($arguments, $options)), $this->status);
    }
}
