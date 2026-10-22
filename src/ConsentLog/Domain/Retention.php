<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\Domain;

/**
 * How many months the log keeps a record.
 */
final readonly class Retention
{
    public const DEFAULT_MONTHS = 12;
    public const MONTHS = [3, 6, 12, 24, 36];

    public function __construct(
        private int $months = self::DEFAULT_MONTHS,
    ) {
        if (!\in_array($months, self::MONTHS, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'The retention must be one of %s months',
                \implode(', ', self::MONTHS)
            ));
        }
    }

    public function months(): int
    {
        return $this->months;
    }

    /**
     * The records before this date are deleted.
     */
    public function cutoff(\DateTimeImmutable $now): \DateTimeImmutable
    {
        return $now->modify(\sprintf('-%d months', $this->months));
    }
}
