<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\Infrastructure;

use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\ConsentLog\Domain\ChoiceCount;
use ItalyCookieChoices\ConsentLog\Domain\ConsentId;
use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyCookieChoices\ConsentLog\Domain\ConsentRecord;

/**
 * The consent log in its own table, the dates are stored in UTC.
 */
final readonly class DatabaseConsentLog implements ConsentLog
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';
    private const BATCH = 500;

    public function __construct(
        private \wpdb $database,
        private LogTable $table,
    ) {
    }

    public function add(ConsentRecord $record): void
    {
        $this->database->insert(
            $this->table->name(),
            [
                'consent_id' => $record->id()->toString(),
                'categories' => \implode(' ', $record->categories()),
                'policy_version' => $record->version()->toString(),
                'recorded_at' => $this->utc($record->recordedAt()),
            ],
            ['%s', '%s', '%s', '%s']
        );
    }

    public function deleteBefore(\DateTimeImmutable $date): int
    {
        $deleted = $this->database->query((string) $this->database->prepare(
            'DELETE FROM %i WHERE recorded_at < %s',
            $this->table->name(),
            $this->utc($date)
        ));

        return \is_int($deleted) ? $deleted : 0;
    }

    /**
     * Read in batches, a large log never sits in memory.
     *
     * @return \Generator<int, ConsentRecord>
     */
    public function all(): \Generator
    {
        $lastId = 0;
        do {
            $rows = $this->database->get_results($this->database->prepare(
                'SELECT id, consent_id, categories, policy_version, recorded_at FROM %i'
                . ' WHERE id > %d ORDER BY id LIMIT %d',
                $this->table->name(),
                $lastId,
                self::BATCH
            ), \ARRAY_A);
            $rows = \is_array($rows) ? $rows : [];

            foreach ($rows as $row) {
                $lastId = (int) $this->field($row, 'id');
                yield $this->recordFrom($row);
            }
        } while (\count($rows) === self::BATCH);
    }

    /**
     * Grouped by the database, the records are never read one by one.
     *
     * @return list<ChoiceCount>
     */
    public function choicesByMonth(): array
    {
        $rows = $this->database->get_results($this->database->prepare(
            'SELECT DATE_FORMAT(recorded_at, %s) AS month, categories, COUNT(*) AS choices FROM %i'
            . ' GROUP BY month, categories',
            '%Y-%m',
            $this->table->name()
        ), \ARRAY_A);

        return \array_map($this->choiceCountFrom(...), \is_array($rows) ? $rows : []);
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function choiceCountFrom(array $row): ChoiceCount
    {
        $categories = $this->field($row, 'categories');

        return new ChoiceCount(
            $this->field($row, 'month'),
            $categories === '' ? [] : \explode(' ', $categories),
            (int) $this->field($row, 'choices')
        );
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function recordFrom(array $row): ConsentRecord
    {
        $categories = $this->field($row, 'categories');

        return new ConsentRecord(
            new ConsentId($this->field($row, 'consent_id')),
            $categories === '' ? [] : \explode(' ', $categories),
            new PolicyVersion($this->field($row, 'policy_version')),
            new \DateTimeImmutable($this->field($row, 'recorded_at'), new \DateTimeZone('UTC'))
        );
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function field(array $row, string $name): string
    {
        $value = $row[$name] ?? '';

        return \is_scalar($value) ? (string) $value : '';
    }

    private function utc(\DateTimeImmutable $date): string
    {
        return $date->setTimezone(new \DateTimeZone('UTC'))->format(self::DATE_FORMAT);
    }
}
