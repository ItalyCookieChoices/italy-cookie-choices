<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\Infrastructure;

use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\ConsentLog\Domain\ConsentId;
use ItalyCookieChoices\ConsentLog\Domain\ConsentRecord;
use ItalyCookieChoices\ConsentLog\Infrastructure\DatabaseConsentLog;
use ItalyCookieChoices\ConsentLog\Infrastructure\LogTable;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class DatabaseConsentLogTest extends WPTestCase
{
    private const ID = '3f2504e0-4f89-41d3-9a0c-0305e82c3301';

    public function testTheTableIsInstalledWithThePlugin(): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $table = new LogTable($wpdb);

        $this->assertTrue($table->isCurrent());
        $this->assertSame($wpdb->prefix . 'icc_consent_log', $table->name());
        $this->assertSame(
            ['id', 'consent_id', 'categories', 'policy_version', 'recorded_at'],
            $wpdb->get_col('DESCRIBE ' . $table->name())
        );
    }

    public function testItStoresTheRecordsInUtc(): void
    {
        $sut = $this->makeSut();

        $sut->add($this->record(['necessary', 'statistics'], '2026-10-22T12:00:00+02:00'));

        global $wpdb;

        /** @var \wpdb $wpdb */
        $this->assertSame(
            [[
                'consent_id' => self::ID,
                'categories' => 'necessary statistics',
                'policy_version' => '1',
                'recorded_at' => '2026-10-22 10:00:00',
            ]],
            $wpdb->get_results(
                'SELECT consent_id, categories, policy_version, recorded_at FROM ' . $wpdb->prefix . 'icc_consent_log',
                \ARRAY_A
            )
        );
    }

    public function testItReadsEveryRecordTheOldestFirst(): void
    {
        $sut = $this->makeSut();
        $sut->add($this->record(['necessary', 'marketing'], '2026-10-01T10:00:00+00:00'));
        $sut->add($this->record([], '2026-10-02T10:00:00+00:00'));

        $records = \iterator_to_array($sut->all(), false);

        $this->assertCount(2, $records);
        $this->assertSame(self::ID, $records[0]->id()->toString());
        $this->assertSame(['necessary', 'marketing'], $records[0]->categories());
        $this->assertSame('1', $records[0]->version()->toString());
        $this->assertSame('2026-10-01T10:00:00+00:00', $records[0]->recordedAt()->format(\DATE_ATOM));
        $this->assertTrue($records[1]->isWithdrawal());
    }

    public function testItReadsALargeLogInBatches(): void
    {
        $sut = $this->makeSut();
        for ($index = 0; $index < 501; $index++) {
            $sut->add($this->record(['necessary'], '2026-10-01T10:00:00+00:00'));
        }

        $this->assertCount(501, \iterator_to_array($sut->all(), false));
    }

    public function testItDeletesTheRecordsBeforeADate(): void
    {
        $sut = $this->makeSut();
        $sut->add($this->record(['necessary'], '2025-01-01T10:00:00+00:00'));
        $sut->add($this->record(['necessary'], '2025-06-01T10:00:00+00:00'));
        $sut->add($this->record(['necessary'], '2026-01-01T10:00:00+00:00'));

        $deleted = $sut->deleteBefore(new \DateTimeImmutable('2025-06-01T13:00:00+02:00'));

        $this->assertSame(2, $deleted);
        $records = \iterator_to_array($sut->all(), false);
        $this->assertCount(1, $records);
        $this->assertSame('2026-01-01', $records[0]->recordedAt()->format('Y-m-d'));
    }

    /**
     * @param list<string> $categories
     */
    private function record(array $categories, string $date): ConsentRecord
    {
        return new ConsentRecord(
            new ConsentId(self::ID),
            $categories,
            new PolicyVersion('1'),
            new \DateTimeImmutable($date)
        );
    }

    private function makeSut(): DatabaseConsentLog
    {
        global $wpdb;
        /** @var \wpdb $wpdb */

        return new DatabaseConsentLog($wpdb, new LogTable($wpdb));
    }
}
