<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI\Cli\Commands;

use ItalyCookieChoices\Console\Commands\CommandInterface;
use ItalyCookieChoices\Console\IO\IOFactory;
use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Scan\UI\Cli\Handlers\ScanSite;
use ItalyCookieChoices\Scan\UI\Cli\Middlewares\ResolveTargets;

/**
 * wp cookie-choices scan
 */
final readonly class ScanCommand implements CommandInterface
{
    public function __construct(
        private CommandRunner $runner,
        private IOFactory $io,
        private ResolveTargets $targets,
        private ScanSite $scan,
    ) {
    }

    public function name(): string
    {
        return 'cookie-choices scan';
    }

    /**
     * Scans the pages of the site and lists the vendors found, then the hosts of the unknown third parties.
     *
     * The pages are read from the server, like a visitor that has not chosen yet: the scan finds the scripts,
     * the frames and the tracking pixels, not the cookies set by JavaScript.
     *
     * ## OPTIONS
     *
     * [--home]
     * : Scan the home page.
     *
     * [--post-type=<post-types>]
     * : The post types to scan, separated by commas, like page,post.
     *
     * [--category=<ids>]
     * : The ids of the categories to scan, separated by commas.
     *
     * [--post=<ids>]
     * : The ids of the posts and pages to scan, separated by commas.
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
     *     wp cookie-choices scan --home --post-type=page
     *     wp cookie-choices scan --category=3,7 --format=json
     *
     * @param array<array-key, mixed> $arguments
     * @param array<array-key, mixed> $options
     */
    public function __invoke(array $arguments, array $options): void
    {
        $this->runner->run(new Message($this->io->create($arguments, $options)), $this->scan, $this->targets);
    }
}
