<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\UI\Cli\Handlers;

use ItalyCookieChoices\Console\IO\IOInterface;
use ItalyCookieChoices\Console\Pipeline\ExitCode;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Console\Pipeline\StatusCode;
use ItalyCookieChoices\Migration\Application\Migrate;
use ItalyCookieChoices\Migration\Application\MigrationSources;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Migration\UI\Cli\MigrationExitCode;
use ItalyCookieChoices\Migration\UI\DismissedSources;
use ItalyCookieChoices\Migration\UI\ImportSummary;
use ItalyStrap\Pipeline\HandlerInterface;

/**
 * Lists the plugins to import from, or shows what the import of one changes and imports it after the confirmation.
 */
final readonly class MigrateSettings implements HandlerInterface
{
    public const DRY_RUN = 'dry-run';

    private const FIELDS = ['source', 'name'];

    public function __construct(
        private MigrationSources $sources,
        private Migrate $migrate,
        private ImportSummary $summary,
        private DismissedSources $dismissed,
    ) {
    }

    public function handle(object $message): ExitCode
    {
        \assert($message instanceof Message);
        $io = $message->io();
        $source = $io->argument(0);
        if ($source === null) {
            $this->list($io);

            return StatusCode::Success;
        }

        try {
            $imported = $this->migrate->preview($source);
        } catch (\InvalidArgumentException) {
            return MigrationExitCode::UnknownSource;
        }

        $this->show($io, $imported);
        if ($io->flag(self::DRY_RUN)) {
            return StatusCode::Success;
        }

        $question = \__('Import these settings into Cookie Choices?', 'italy-cookie-choices');
        if (!$io->confirm($question)) {
            return StatusCode::Cancelled;
        }

        $this->migrate->import($source);
        $this->dismissed->add($source);
        $io->success(\__('The settings are imported.', 'italy-cookie-choices'));

        return StatusCode::Success;
    }

    private function list(IOInterface $io): void
    {
        $io->items(
            \array_map(
                static fn(MigrationSource $source): array => ['source' => $source->id(), 'name' => $source->name()],
                $this->sources->found()
            ),
            self::FIELDS
        );
    }

    private function show(IOInterface $io, ImportedSettings $imported): void
    {
        $io->line(\__('The import changes:', 'italy-cookie-choices'));
        foreach ($this->summary->lines($imported) as $line) {
            $io->line('* ' . $line);
        }

        if ($imported->notes() === []) {
            return;
        }

        $io->line(\__('To check:', 'italy-cookie-choices'));
        foreach ($imported->notes() as $note) {
            $io->line('* ' . $note);
        }
    }
}
