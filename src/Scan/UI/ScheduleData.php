<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI;

use ItalyCookieChoices\Blocking\Application\VendorSettings;
use ItalyCookieChoices\Scan\Application\ScanReports;
use ItalyCookieChoices\Scan\Application\ScanSchedule;

/**
 * The schedule of the scan for the settings page, with the names of the chosen categories and posts,
 * and the last report of the scheduled scan.
 */
final readonly class ScheduleData
{
    public function __construct(
        private ScanSchedule $schedule,
        private ScanReports $reports,
        private VendorSettings $vendors,
    ) {
    }

    /**
     * @return array{
     *     frequency: string,
     *     home: bool,
     *     postTypes: list<string>,
     *     categories: list<array{id: int, name: string}>,
     *     posts: list<array{id: int, name: string}>,
     *     email: bool,
     *     adminEmail: string,
     *     report: array{date: string, pages: int, errors: int, vendors: list<string>, hosts: list<string>}|null
     * }
     */
    public function data(): array
    {
        $schedule = $this->schedule->get();

        return [
            'frequency' => $schedule->frequency->value,
            'home' => $schedule->selection->home,
            'postTypes' => $schedule->selection->postTypes,
            'categories' => $this->terms($schedule->selection->categories, $this->categoryName(...)),
            'posts' => $this->terms($schedule->selection->posts, $this->postName(...)),
            'email' => $schedule->email,
            'adminEmail' => $this->option('admin_email'),
            'report' => $this->report(),
        ];
    }

    /**
     * @return array{date: string, pages: int, errors: int, vendors: list<string>, hosts: list<string>}|null
     */
    private function report(): ?array
    {
        $report = $this->reports->last();
        if ($report === null) {
            return null;
        }

        $names = \array_column($this->vendors->all(), 'name', 'id');
        $date = \wp_date($this->option('date_format'), $report->date->getTimestamp());

        return [
            'date' => \is_string($date) ? $date : '',
            'pages' => $report->pages,
            'errors' => $report->errors,
            'vendors' => \array_map(static fn(string $id): string => $names[$id] ?? $id, $report->vendors),
            'hosts' => $report->hosts,
        ];
    }

    /**
     * The terms that still exist, with their names.
     *
     * @param list<int> $ids
     * @param \Closure(int): string $name
     * @return list<array{id: int, name: string}>
     */
    private function terms(array $ids, \Closure $name): array
    {
        $terms = [];
        foreach ($ids as $id) {
            $termName = $name($id);
            if ($termName === '') {
                continue;
            }

            $terms[] = ['id' => $id, 'name' => $termName];
        }

        return $terms;
    }

    private function option(string $name): string
    {
        $value = \get_option($name);

        return \is_string($value) ? $value : '';
    }

    private function categoryName(int $id): string
    {
        $category = \get_term($id, 'category');

        return $category instanceof \WP_Term ? \html_entity_decode($category->name, \ENT_QUOTES) : '';
    }

    private function postName(int $id): string
    {
        $post = \get_post($id);

        return $post instanceof \WP_Post ? \html_entity_decode(\get_the_title($post), \ENT_QUOTES) : '';
    }
}
