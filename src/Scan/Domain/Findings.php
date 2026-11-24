<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Domain;

/**
 * What the scan found in a page: the known vendors, the hosts of the third parties no vendor covers,
 * and whether it loads the Google Fonts from Google.
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
        private bool $loadsGoogleFonts = false,
    ) {
    }

    /**
     * Whether the page loads fonts from the servers of Google, they get the IP address of the visitor.
     */
    public function loadsGoogleFonts(): bool
    {
        return $this->loadsGoogleFonts;
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
