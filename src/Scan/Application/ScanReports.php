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
        if (!\is_array($option) || !\is_string($option['date'] ?? null)) {
            return null;
        }

        try {
            $date = new \DateTimeImmutable($option['date']);
        } catch (\Exception) {
            return null;
        }

        return new Report(
            $date,
            \is_int($option['pages'] ?? null) ? $option['pages'] : 0,
            \is_int($option['errors'] ?? null) ? $option['errors'] : 0,
            $this->strings($option['vendors'] ?? null),
            $this->strings($option['hosts'] ?? null),
            $this->strings($option['newVendors'] ?? null),
            $this->strings($option['newHosts'] ?? null),
        );
    }

    public function save(Report $report): void
    {
        \update_option(self::OPTION, [
            'date' => $report->date->format(\DATE_ATOM),
            'pages' => $report->pages,
            'errors' => $report->errors,
            'vendors' => $report->vendors,
            'hosts' => $report->hosts,
            'newVendors' => $report->newVendors,
            'newHosts' => $report->newHosts,
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
