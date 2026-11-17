<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Domain;

/**
 * What the scan found in a page: the known vendors, and the hosts of the third parties no vendor covers.
 */
final readonly class Findings
{
    /**
     * @param list<string> $vendors The ids of the vendors, without duplicates.
     * @param list<string> $hosts The hosts of the unknown third parties, without duplicates.
     */
    public function __construct(
        private array $vendors,
        private array $hosts,
    ) {
    }

    /**
     * @return list<string>
     */
    public function vendors(): array
    {
        return $this->vendors;
    }

    /**
     * @return list<string>
     */
    public function hosts(): array
    {
        return $this->hosts;
    }
}
