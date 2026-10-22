<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Domain;

/**
 * How long a consent lasts before the visitor is asked again.
 */
final readonly class ConsentDuration
{
    /**
     * Six months, as the Italian Data Protection Authority recommends.
     */
    public const DEFAULT_DAYS = 182;

    public function __construct(
        private int $days = self::DEFAULT_DAYS,
    ) {
        if ($days < 1) {
            throw new \InvalidArgumentException('The consent duration must be at least one day');
        }
    }

    public function days(): int
    {
        return $this->days;
    }

    public function expiresAt(\DateTimeImmutable $givenAt): \DateTimeImmutable
    {
        return $givenAt->modify(\sprintf('+%d days', $this->days));
    }
}
