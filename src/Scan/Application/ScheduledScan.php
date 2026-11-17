<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

use ItalyCookieChoices\Blocking\Application\VendorSettings;
use ItalyCookieChoices\Scan\Domain\Report;

/**
 * The scan that runs on WP-Cron: start() lists the pages of the schedule, each next() scans one batch,
 * the cron event runs next() again until it returns false. The last batch writes the report.
 *
 * The progress is kept in its own option between the cron events.
 */
final readonly class ScheduledScan
{
    public const RUN_OPTION = 'italy_cookie_choices_scan_run';
    private const BATCH = 10;

    public function __construct(
        private ScanSchedule $schedule,
        private ScanReports $reports,
        private ScanTargets $targets,
        private SiteScanner $scanner,
        private VendorSettings $vendors,
    ) {
    }

    /**
     * @return bool Whether there are pages to scan.
     */
    public function start(): bool
    {
        $ids = \array_column($this->targets->of($this->schedule->get()->selection)->targets(), 'id');
        if ($ids === []) {
            \delete_option(self::RUN_OPTION);
            return false;
        }

        $this->saveRun(['ids' => $ids, 'vendors' => [], 'hosts' => [], 'pages' => 0, 'errors' => 0]);

        return true;
    }

    /**
     * Scans the next batch.
     *
     * @return bool Whether there are pages left, false after the report is written.
     */
    public function next(): bool
    {
        $run = $this->run();
        if ($run === null) {
            return false;
        }

        foreach ($this->scanner->scan(\array_slice($run['ids'], 0, self::BATCH)) as $page) {
            $run['vendors'] = [...$run['vendors'], ...$page['vendors']];
            $run['hosts'] = [...$run['hosts'], ...$page['hosts']];
            $run['pages']++;
            $run['errors'] += $page['error'] === '' ? 0 : 1;
        }

        $run['ids'] = \array_slice($run['ids'], self::BATCH);
        if ($run['ids'] !== []) {
            $this->saveRun($run);
            return true;
        }

        \delete_option(self::RUN_OPTION);
        $this->reports->save($this->report($run));

        return false;
    }

    /**
     * @param array{ids: list<int>, vendors: list<string>, hosts: list<string>, pages: int, errors: int} $run
     */
    private function report(array $run): Report
    {
        $notBlocked = \array_column(\array_filter(
            $this->vendors->all(),
            static fn(array $vendor): bool => !$vendor['enabled']
        ), 'id');
        $vendors = \array_values(\array_intersect($notBlocked, \array_unique($run['vendors'])));
        $hosts = \array_values(\array_unique($run['hosts']));
        $previous = $this->reports->last();

        return new Report(
            new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
            $run['pages'],
            $run['errors'],
            $vendors,
            $hosts,
            \array_values(\array_diff($vendors, $previous === null ? [] : $previous->vendors)),
            \array_values(\array_diff($hosts, $previous === null ? [] : $previous->hosts)),
        );
    }

    /**
     * @return array{ids: list<int>, vendors: list<string>, hosts: list<string>, pages: int, errors: int}|null
     */
    private function run(): ?array
    {
        $run = \get_option(self::RUN_OPTION);
        if (!\is_array($run) || !\is_array($run['ids'] ?? null)) {
            return null;
        }

        return [
            'ids' => \array_values(\array_filter($run['ids'], \is_int(...))),
            'vendors' => $this->strings($run['vendors'] ?? null),
            'hosts' => $this->strings($run['hosts'] ?? null),
            'pages' => \is_int($run['pages'] ?? null) ? $run['pages'] : 0,
            'errors' => \is_int($run['errors'] ?? null) ? $run['errors'] : 0,
        ];
    }

    /**
     * @param array{ids: list<int>, vendors: list<string>, hosts: list<string>, pages: int, errors: int} $run
     */
    private function saveRun(array $run): void
    {
        \update_option(self::RUN_OPTION, $run, false);
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $value): array
    {
        return \array_values(\array_filter(\is_array($value) ? $value : [], \is_string(...)));
    }
}
