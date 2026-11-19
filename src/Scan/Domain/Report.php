<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Domain;

/**
 * The report of the last scheduled scan: what needs an action, and what the scan before did not report.
 */
final readonly class Report
{
    /** The names of the fields, in the saved report and in the page data. */
    public const DATE = 'date';
    public const PAGES = 'pages';
    public const ERRORS = 'errors';
    public const VENDORS = 'vendors';
    public const HOSTS = 'hosts';
    public const NEW_VENDORS = 'newVendors';
    public const NEW_HOSTS = 'newHosts';

    /**
     * @param list<string> $vendors The vendors found and not blocked.
     * @param list<string> $hosts The unknown third parties found.
     * @param list<string> $newVendors The vendors the scan before did not report.
     * @param list<string> $newHosts The third parties the scan before did not report.
     */
    public function __construct(
        public \DateTimeImmutable $date,
        public int $pages,
        public int $errors,
        public array $vendors,
        public array $hosts,
        public array $newVendors,
        public array $newHosts,
    ) {
    }

    public function hasNews(): bool
    {
        return $this->newVendors !== [] || $this->newHosts !== [];
    }

    /**
     * The same report, after the admin has seen the news.
     */
    public function withoutNews(): self
    {
        return new self($this->date, $this->pages, $this->errors, $this->vendors, $this->hosts, [], []);
    }
}
