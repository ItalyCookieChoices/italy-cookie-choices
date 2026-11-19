<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

use ItalyCookieChoices\Scan\Domain\Report;

/**
 * The report of the last scheduled scan, in its own option.
 */
final readonly class ScanReports
{
    public const OPTION = 'italy_cookie_choices_scan_report';

    /**
     * @phpstan-impure
     */
    public function last(): ?Report
    {
        $option = \get_option(self::OPTION);
        if (!\is_array($option) || !\is_string($option[Report::DATE] ?? null)) {
            return null;
        }

        try {
            $date = new \DateTimeImmutable($option[Report::DATE]);
        } catch (\Exception) {
            return null;
        }

        return new Report(
            $date,
            \is_int($option[Report::PAGES] ?? null) ? $option[Report::PAGES] : 0,
            \is_int($option[Report::ERRORS] ?? null) ? $option[Report::ERRORS] : 0,
            $this->strings($option[Report::VENDORS] ?? null),
            $this->strings($option[Report::HOSTS] ?? null),
            $this->strings($option[Report::NEW_VENDORS] ?? null),
            $this->strings($option[Report::NEW_HOSTS] ?? null),
        );
    }

    public function save(Report $report): void
    {
        \update_option(self::OPTION, [
            Report::DATE => $report->date->format(\DATE_ATOM),
            Report::PAGES => $report->pages,
            Report::ERRORS => $report->errors,
            Report::VENDORS => $report->vendors,
            Report::HOSTS => $report->hosts,
            Report::NEW_VENDORS => $report->newVendors,
            Report::NEW_HOSTS => $report->newHosts,
        ], false);
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $value): array
    {
        return \array_values(\array_filter(\is_array($value) ? $value : [], \is_string(...)));
    }
}
