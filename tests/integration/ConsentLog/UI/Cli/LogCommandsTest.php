<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\UI\Cli;

use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\Console\Commands\CommandsConfigExtension;
use ItalyCookieChoices\Console\Middlewares\OpenOutput;
use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\ConsentLog\Domain\ConsentId;
use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyCookieChoices\ConsentLog\Domain\ConsentRecord;
use ItalyCookieChoices\ConsentLog\Module;
use ItalyCookieChoices\ConsentLog\UI\Cli\Commands\LogExportCommand;
use ItalyCookieChoices\ConsentLog\UI\Cli\Commands\LogNamespace;
use ItalyCookieChoices\ConsentLog\UI\Cli\Commands\LogPurgeCommand;
use ItalyCookieChoices\ConsentLog\UI\Cli\Handlers\PurgeLog;
use ItalyCookieChoices\ConsentLog\UI\Cli\Handlers\WriteConsentLog;
use ItalyCookieChoices\ConsentLog\UI\Cli\Middlewares\ResolvePurgeDate;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\FakeIO;
use ItalyCookieChoices\Tests\Support\FakeIOFactory;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class LogCommandsTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1', 'consent_log' => '1', 'consent_log_retention' => '12']);
        \update_option('timezone_string', 'Europe/Rome');
        $log = $this->service(ConsentLog::class);
        $log->add($this->record('3f2504e0-4f89-41d3-9a0c-0305e82c3301', '-2 years'));
        $log->add($this->record('3f2504e0-4f89-41d3-9a0c-0305e82c3302', '-6 months'));
        $log->add($this->record('3f2504e0-4f89-41d3-9a0c-0305e82c3303', '-1 day'));
    }

    public function testTheModuleListsTheLogCommands(): void
    {
        $config = (new Module('/plugin/', 'https://example.com/plugin/'))();

        $this->assertIsArray($config);
        $this->assertSame(
            ['cookie-choices log' => LogNamespace::class, LogExportCommand::class, LogPurgeCommand::class],
            $config[CommandsConfigExtension::COMMANDS]
        );
        $this->assertSame('cookie-choices log export', $this->service(LogExportCommand::class)->name());
        $this->assertSame('cookie-choices log purge', $this->service(LogPurgeCommand::class)->name());
    }

    public function testTheLogIsExportedAsCsv(): void
    {
        $file = \wp_tempnam('consent-log.csv');
        $factory = new FakeIOFactory();
        $command = new LogExportCommand(
            new CommandRunner(),
            $factory,
            new OpenOutput(),
            $this->service(WriteConsentLog::class)
        );

        $command([], ['file' => $file]);

        $lines = \file($file, \FILE_IGNORE_NEW_LINES) ?: [];
        \unlink($file);
        $this->assertSame(0, $factory->io()->exit);
        $this->assertSame('consent_id,recorded_at,policy_version,categories,withdrawn', $lines[0] ?? null);
        $this->assertCount(4, $lines);
        $this->assertSame(['The consent log is exported to ' . $file . '.'], $factory->io()->successes);
    }

    public function testTheRecordsOlderThanTheRetentionArePurged(): void
    {
        $io = $this->purge(['yes' => true]);

        $this->assertSame(0, $io->exit);
        $this->assertSame(['1 record deleted.'], $io->successes);
        $this->assertSame(2, $this->records());
    }

    public function testTheRecordsBeforeADateArePurged(): void
    {
        $before = (new \DateTimeImmutable('-1 month', \wp_timezone()))->format('Y-m-d');

        $io = $this->purge(['before' => $before, 'yes' => true]);

        $this->assertSame(0, $io->exit);
        $this->assertSame(['2 records deleted.'], $io->successes);
    }

    public function testAllTheRecordsArePurged(): void
    {
        $io = $this->purge(['all' => true, 'yes' => true]);

        $this->assertSame(['3 records deleted.'], $io->successes);
        $this->assertSame(0, $this->records());
    }

    public function testThePurgeAsksToConfirmWithTheDate(): void
    {
        $io = $this->purge(['before' => '2026-01-01'], false);

        $this->assertSame(2, $io->exit);
        $this->assertSame(['Delete the records of the consent log before 2026-01-01 00:00?'], $io->questions);
        $this->assertSame(3, $this->records());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function invalidDates(): iterable
    {
        yield 'not a date' => ['yesterday'];
        yield 'another format' => ['01/02/2026'];
        yield 'a day that does not exist' => ['2026-02-30'];
    }

    /**
     * @dataProvider invalidDates
     */
    public function testAnInvalidDateIsAnError(string $date): void
    {
        $io = $this->purge(['before' => $date, 'yes' => true]);

        $this->assertSame(6, $io->exit);
        $this->assertSame(['The date is not valid, use the format YYYY-MM-DD.'], $io->errors);
        $this->assertSame(3, $this->records());
    }

    /**
     * @param array<string, string|true> $options
     */
    private function purge(array $options, bool $confirms = true): FakeIO
    {
        $factory = new FakeIOFactory($confirms);
        $command = new LogPurgeCommand(
            new CommandRunner(),
            $factory,
            $this->service(ResolvePurgeDate::class),
            $this->service(PurgeLog::class)
        );

        $command([], $options);

        return $factory->io();
    }

    private function records(): int
    {
        return \count(\iterator_to_array($this->service(ConsentLog::class)->all(), false));
    }

    private function record(string $id, string $date): ConsentRecord
    {
        return new ConsentRecord(
            new ConsentId($id),
            ['necessary'],
            new PolicyVersion('1'),
            new \DateTimeImmutable($date)
        );
    }
}
