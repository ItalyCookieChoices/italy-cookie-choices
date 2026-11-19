<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\UI;

use ItalyCookieChoices\Console\Commands\CommandsConfigExtension;
use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\Migration\Application\BuildingMigrationSources;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Module;
use ItalyCookieChoices\Migration\UI\Cli\Commands\MigrateCommand;
use ItalyCookieChoices\Migration\UI\Cli\Handlers\MigrateSettings;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\FakeIO;
use ItalyCookieChoices\Tests\Support\FakeIOFactory;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyCookieChoices\Tests\Support\Migration\FakeMigrationSource;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class MigrateCommandTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;

    public function setUp(): void
    {
        parent::setUp();
        $this->logInAs('administrator');
        \add_action(BuildingMigrationSources::class, static function (BuildingMigrationSources $event): void {
            $event->addSource(new FakeMigrationSource(
                'other',
                new ImportedSettings(['text' => 'Imported'], [], ['Check the colors.'])
            ));
        });
    }

    public function testTheModuleListsTheMigrateCommand(): void
    {
        $config = (new Module())();

        $this->assertIsArray($config);
        $this->assertSame([MigrateCommand::class], $config[CommandsConfigExtension::COMMANDS]);
        $this->assertSame('cookie-choices migrate', $this->service(MigrateCommand::class)->name());
    }

    public function testWithoutASourceItListsThePlugins(): void
    {
        $io = $this->migrate([], []);

        $this->assertContains(['source' => 'other', 'name' => 'Other'], $io->items);
        $this->assertSame(['source', 'name'], $io->fields);
    }

    public function testTheDryRunShowsTheChangesWithoutImporting(): void
    {
        $io = $this->migrate(['other'], ['dry-run' => true]);

        $this->assertContains('* The texts of the banner and of the buttons', $io->lines);
        $this->assertContains('* Check the colors.', $io->lines);
        $this->assertSame(0, $io->exit);
        $this->assertSame([], $io->errors);
        $this->assertSame([], $io->questions);
        $this->assertNotSame('Imported', $this->savedText());
    }

    public function testWithoutTheConfirmationNothingIsImported(): void
    {
        $io = $this->migrate(['other'], [], false);

        $this->assertSame(['Nothing changed.'], $io->errors);
        $this->assertSame(2, $io->exit);
        $this->assertNotSame('Imported', $this->savedText());
    }

    public function testItImportsAfterTheConfirmation(): void
    {
        $io = $this->migrate(['other'], ['yes' => true]);

        $this->assertSame(['The settings are imported.'], $io->successes);
        $this->assertSame('Imported', $this->savedText());
    }

    public function testAnUnknownPluginFails(): void
    {
        $io = $this->migrate(['unknown'], []);

        $this->assertSame(8, $io->exit);
    }

    /**
     * @param list<string> $arguments
     * @param array<string, mixed> $options
     */
    private function migrate(array $arguments, array $options, bool $confirms = true): FakeIO
    {
        $factory = new FakeIOFactory($confirms);
        $command = new MigrateCommand(new CommandRunner(), $factory, $this->service(MigrateSettings::class));
        $command($arguments, $options);

        return $factory->io();
    }

    private function savedText(): mixed
    {
        $option = \get_option(Schema::OPTION);

        return \is_array($option) ? $option['text'] ?? null : null;
    }
}
