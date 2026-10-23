<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\UI;

use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\ConsentLog\Application\LogSettings;
use ItalyCookieChoices\ConsentLog\Domain\ConsentId;
use ItalyCookieChoices\ConsentLog\Domain\ConsentRecord;
use ItalyCookieChoices\ConsentLog\Infrastructure\DatabaseConsentLog;
use ItalyCookieChoices\ConsentLog\Infrastructure\LogTable;
use ItalyCookieChoices\ConsentLog\UI\RetentionCleanup;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class RetentionCleanupTest extends WPTestCase
{
    use BuildsOptions;

    public function setUp(): void
    {
        parent::setUp();
        \wp_clear_scheduled_hook(RetentionCleanup::HOOK);
        \update_option(Schema::OPTION, ['active' => '1', 'consent_log' => '1', 'consent_log_retention' => '6']);
    }

    public function tearDown(): void
    {
        \wp_clear_scheduled_hook(RetentionCleanup::HOOK);
        parent::tearDown();
    }

    public function testItSchedulesTheCleanupAndRunsIt(): void
    {
        $this->assertSame(
            ['init' => 'schedule', RetentionCleanup::HOOK => 'cleanup'],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testItSchedulesADailyCleanup(): void
    {
        $this->makeSut()->schedule();

        $this->assertSame('daily', \wp_get_schedule(RetentionCleanup::HOOK));
    }

    public function testItSchedulesTheCleanupOnce(): void
    {
        $this->makeSut()->schedule();
        $first = \wp_next_scheduled(RetentionCleanup::HOOK);

        $this->makeSut()->schedule();

        $this->assertSame($first, \wp_next_scheduled(RetentionCleanup::HOOK));
    }

    public function testNothingIsScheduledWhenTheLogIsOff(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $this->makeSut()->schedule();

        $this->assertFalse(\wp_next_scheduled(RetentionCleanup::HOOK));
    }

    public function testTheCleanupDeletesTheRecordsOlderThanTheRetention(): void
    {
        $log = $this->log();
        $log->add($this->record('-7 months'));
        $log->add($this->record('-5 months'));

        $this->assertSame(1, $this->makeSut()->cleanup());
        $this->assertCount(1, \iterator_to_array($log->all(), false));
    }

    public function testAnInvalidRetentionKeepsTwelveMonths(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'consent_log' => '1', 'consent_log_retention' => '7']);
        $log = $this->log();
        $log->add($this->record('-13 months'));
        $log->add($this->record('-11 months'));

        $this->assertSame(1, $this->makeSut()->cleanup());
    }

    private function record(string $age): ConsentRecord
    {
        return new ConsentRecord(
            new ConsentId('3f2504e0-4f89-41d3-9a0c-0305e82c3301'),
            ['necessary'],
            new PolicyVersion('1'),
            new \DateTimeImmutable($age)
        );
    }

    private function log(): DatabaseConsentLog
    {
        global $wpdb;
        /** @var \wpdb $wpdb */

        return new DatabaseConsentLog($wpdb, new LogTable($wpdb));
    }

    private function makeSut(): RetentionCleanup
    {
        return new RetentionCleanup($this->log(), new LogSettings($this->options()));
    }
}
