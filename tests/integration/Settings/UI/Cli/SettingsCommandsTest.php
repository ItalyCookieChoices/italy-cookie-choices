<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings\UI\Cli;

use ItalyCookieChoices\Console\Commands\CommandsConfigExtension;
use ItalyCookieChoices\Console\Middlewares\OpenOutput;
use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\Settings\Module;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Settings\UI\Cli\Commands\SettingsExportCommand;
use ItalyCookieChoices\Settings\UI\Cli\Commands\SettingsImportCommand;
use ItalyCookieChoices\Settings\UI\Cli\Commands\SettingsNamespace;
use ItalyCookieChoices\Settings\UI\Cli\Handlers\ImportSettings;
use ItalyCookieChoices\Settings\UI\Cli\Handlers\WriteSettingsFile;
use ItalyCookieChoices\Settings\UI\Cli\Middlewares\ReadSettingsFile;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\FakeIO;
use ItalyCookieChoices\Tests\Support\FakeIOFactory;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SettingsCommandsTest extends WPTestCase
{
    use BuildsContainer;

    private string $folder = '';

    public function setUp(): void
    {
        parent::setUp();
        $this->folder = \get_temp_dir() . 'icc-cli-' . \wp_generate_password(8, false);
        \wp_mkdir_p($this->folder);
        \update_option(Schema::OPTION, ['active' => '1', 'text' => 'Exported text', 'banner' => '2']);
    }

    public function tearDown(): void
    {
        foreach (\glob($this->folder . '/*') ?: [] as $file) {
            \unlink($file);
        }

        \rmdir($this->folder);
        parent::tearDown();
    }

    public function testTheModuleListsTheSettingsCommands(): void
    {
        $config = (new Module('/plugin/', 'https://example.com/plugin/', 'plugin/plugin.php'))();

        $this->assertIsArray($config);
        $this->assertSame(
            [
                'cookie-choices settings' => SettingsNamespace::class,
                SettingsExportCommand::class,
                SettingsImportCommand::class,
            ],
            $config[CommandsConfigExtension::COMMANDS]
        );
        $this->assertSame('cookie-choices settings export', $this->service(SettingsExportCommand::class)->name());
        $this->assertSame('cookie-choices settings import', $this->service(SettingsImportCommand::class)->name());
    }

    public function testTheSettingsAreExportedToAFile(): void
    {
        $file = $this->folder . '/settings.json';

        $io = $this->export(['file' => $file]);

        $this->assertSame(0, $io->exit);
        $exported = \json_decode((string) \file_get_contents($file), true);
        $this->assertIsArray($exported);
        $this->assertSame('italy-cookie-choices', $exported['plugin'] ?? null);
        $this->assertSame(Schema::VERSION, $exported['version'] ?? null);
        $this->assertIsArray($exported['settings'] ?? null);
        $this->assertSame('Exported text', $exported['settings']['text'] ?? null);
        $this->assertSame(['The settings are exported to ' . $file . '.'], $io->successes);
    }

    public function testTheSettingsAreExportedToTheStandardOutput(): void
    {
        \ob_start();
        $io = $this->export([]);
        $output = (string) \ob_get_clean();

        $this->assertSame(0, $io->exit);
        $exported = \json_decode($output, true);
        $this->assertIsArray($exported);
        $this->assertSame('italy-cookie-choices', $exported['plugin'] ?? null);
        $this->assertSame([], $io->successes);
    }

    public function testAFileThatCannotBeWrittenIsAnError(): void
    {
        $io = $this->export(['file' => $this->folder . '/missing/settings.json']);

        $this->assertSame(['The file cannot be written.'], $io->errors);
        $this->assertSame(3, $io->exit);
    }

    public function testTheExportedSettingsAreImported(): void
    {
        $file = $this->folder . '/settings.json';
        $this->export(['file' => $file]);
        \update_option(Schema::OPTION, ['active' => '1', 'text' => 'Changed']);

        $io = $this->import([$file], ['yes' => true]);

        $this->assertSame(0, $io->exit);
        $this->assertSame('Exported text', $this->saved()['text'] ?? null);
        $this->assertSame('2', $this->saved()['banner'] ?? null);
        $this->assertSame(['The settings are imported.'], $io->successes);
    }

    public function testTheFileOfThe2xVersionsIsMigrated(): void
    {
        $file = $this->folder . '/legacy.json';
        \file_put_contents($file, (string) \wp_json_encode([['active' => '1', 'text' => 'Legacy', 'scroll' => '1']]));

        $io = $this->import([$file], ['yes' => true]);

        $this->assertSame(0, $io->exit);
        $this->assertSame('Legacy', $this->saved()['text'] ?? null);
        $this->assertArrayNotHasKey('scroll', $this->saved());
    }

    public function testTheImportAsksToConfirm(): void
    {
        $file = $this->folder . '/settings.json';
        $this->export(['file' => $file]);
        \update_option(Schema::OPTION, ['active' => '1', 'text' => 'Changed']);

        $io = $this->import([$file], [], false);

        $this->assertSame(2, $io->exit);
        $this->assertSame(['Replace the settings of Cookie Choices with the ones of the file?'], $io->questions);
        $this->assertSame(['Nothing changed.'], $io->errors);
        $this->assertSame('Changed', $this->saved()['text'] ?? null);
    }

    /**
     * @return iterable<string, array{string|null, int, string}>
     */
    public function invalidFiles(): iterable
    {
        yield 'no argument' => [null, 4, 'The file cannot be read.'];
        yield 'missing file' => ['missing.json', 4, 'The file cannot be read.'];
        yield 'not JSON' => ['not JSON', 5, 'The file has no settings of Cookie Choices.'];
        yield 'no settings' => [
            '{"plugin":"italy-cookie-choices","version":10}',
            5,
            'The file has no settings of Cookie Choices.',
        ];
        yield 'newer version' => [
            '{"version":999,"settings":{"active":"1"}}',
            5,
            'The file has no settings of Cookie Choices.',
        ];
    }

    /**
     * @dataProvider invalidFiles
     */
    public function testAnInvalidFileIsNotImported(?string $content, int $code, string $error): void
    {
        $arguments = [];
        if ($content !== null) {
            $arguments[] = $this->folder . '/' . ($content === 'missing.json' ? 'missing.json' : 'file.json');
        }

        if ($content !== null && $content !== 'missing.json') {
            \file_put_contents($arguments[0], $content);
        }

        $io = $this->import($arguments, ['yes' => true]);

        $this->assertSame($code, $io->exit);
        $this->assertSame([$error], $io->errors);
        $this->assertSame('Exported text', $this->saved()['text'] ?? null);
    }

    /**
     * @param array<string, string> $options
     */
    private function export(array $options): FakeIO
    {
        $factory = new FakeIOFactory();
        $command = new SettingsExportCommand(
            new CommandRunner(),
            $factory,
            new OpenOutput(),
            $this->service(WriteSettingsFile::class)
        );

        $command([], $options);

        return $factory->io();
    }

    /**
     * @param list<string> $arguments
     * @param array<string, true> $options
     */
    private function import(array $arguments, array $options, bool $confirms = true): FakeIO
    {
        $factory = new FakeIOFactory($confirms);
        $command = new SettingsImportCommand(
            new CommandRunner(),
            $factory,
            new ReadSettingsFile(),
            $this->service(ImportSettings::class)
        );

        $command($arguments, $options);

        return $factory->io();
    }

    /**
     * @return array<array-key, mixed>
     */
    private function saved(): array
    {
        $saved = \get_option(Schema::OPTION);
        $this->assertIsArray($saved);

        return $saved;
    }
}
