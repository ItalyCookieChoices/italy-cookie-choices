<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\Domain;

/**
 * Where the consents are kept to demonstrate them, GDPR art. 7(1).
 */
interface ConsentLog
{
    public function add(ConsentRecord $record): void;

    /**
     * Deletes the records older than the date, returns how many.
     */
    public function deleteBefore(\DateTimeImmutable $date): int;

    /**
     * Every record, the oldest first.
     *
     * @return iterable<ConsentRecord>
     */
    public function all(): iterable;
}
