<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\UI\Cli\Commands;

use ItalyCookieChoices\Console\Commands\CommandInterface;
use ItalyCookieChoices\Console\IO\IOFactory;
use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Migration\UI\Cli\Handlers\MigrateSettings;

/**
 * wp cookie-choices migrate
 */
final readonly class MigrateCommand implements CommandInterface
{
    public function __construct(
        private CommandRunner $runner,
        private IOFactory $io,
        private MigrateSettings $migrate,
    ) {
    }

    public function name(): string
    {
        return 'cookie-choices migrate';
    }

    /**
     * Imports the settings of another consent plugin, also when it is deactivated.
     *
     * Without a source it lists the plugins whose settings are in the database. The imported settings replace
     * the ones of Cookie Choices, the others stay; the visitors are asked for the consent again.
     *
     * ## OPTIONS
     *
     * [<source>]
     * : The plugin to import from, like complianz.
     *
     * [--dry-run]
     * : Show what the import changes, without importing.
     *
     * [--yes]
     * : Import without the confirmation.
     *
     * [--format=<format>]
     * : The format of the list of the plugins.
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
     *     wp cookie-choices migrate
     *     wp cookie-choices migrate complianz --dry-run
     *     wp cookie-choices migrate complianz --yes
     *
     * @param array<array-key, mixed> $arguments
     * @param array<array-key, mixed> $options
     */
    public function __invoke(array $arguments, array $options): void
    {
        $this->runner->run(new Message($this->io->create($arguments, $options)), $this->migrate);
    }
}
