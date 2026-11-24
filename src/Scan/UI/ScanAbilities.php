<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI;

use ItalyCookieChoices\Scan\Application\ScanReports;
use ItalyCookieChoices\Scan\Application\ScanTargets;
use ItalyCookieChoices\Scan\Application\SiteScanner;
use ItalyCookieChoices\Scan\Domain\Report;
use ItalyCookieChoices\Scan\Domain\Selection;
use ItalyCookieChoices\Settings\UI\Abilities;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The abilities of the scan of the site:
 *
 * * italy-cookie-choices/scan-site reads a batch of the chosen pages and finds their vendors and unknown third parties;
 * * italy-cookie-choices/get-scan-report returns the report of the last scheduled scan.
 */
final readonly class ScanAbilities implements SubscriberInterface
{
    private const OFFSET = 'offset';

    public function __construct(
        private Abilities $abilities,
        private ScanTargets $targets,
        private SiteScanner $scanner,
        private ScanReports $reports,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield Hook::ABILITIES_API_INIT => 'register';
    }

    public function register(): void
    {
        $texts = ['type' => 'array', 'items' => ['type' => 'string']];
        $ids = ['type' => 'array', 'items' => ['type' => 'integer', 'minimum' => 0]];
        $count = ['type' => 'integer'];

        $this->abilities->register('scan-site', [
            'label' => \__('Scan the site', 'italy-cookie-choices'),
            'description' => \implode(' ', [
                \sprintf(
                    /* translators: %d: the number of pages of a batch. */
                    \__('Reads the chosen pages from the server, %d at a time.', 'italy-cookie-choices'),
                    ScanEndpoint::BATCH
                ),
                \__(
                    'It finds the vendors of their scripts, frames and pixels, and the unknown third parties.',
                    'italy-cookie-choices'
                ),
                \__('Run it again with nextOffset until it is null.', 'italy-cookie-choices'),
                \__(
                    'It changes nothing: block the vendors with switch-vendor, add the others with add-custom-vendor.',
                    'italy-cookie-choices'
                ),
            ]),
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    Selection::HOME => ['type' => 'boolean', 'default' => false],
                    Selection::POST_TYPES => [...$texts, 'description' => 'Post types, like page and post.'],
                    Selection::CATEGORIES => [...$ids, 'description' => 'The ids of the categories of the posts.'],
                    Selection::POSTS => [...$ids, 'description' => 'The ids of single posts and pages.'],
                    self::OFFSET => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
                ],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'total' => $count,
                    'truncated' => [
                        'type' => 'boolean',
                        'description' => 'True when the selection has more pages than the limit.',
                    ],
                    'nextOffset' => ['type' => ['integer', 'null']],
                    'pages' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => $count,
                                'url' => ['type' => 'string'],
                                'vendors' => [...$texts, 'description' => 'The ids of the vendors, from list-vendors.'],
                                'hosts' => [...$texts, 'description' => 'The unknown third parties.'],
                                'googleFonts' => [
                                    'type' => 'boolean',
                                    'description' => 'True when the page loads fonts from Google.',
                                ],
                                'error' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
            'execute_callback' => $this->scan(...),
        ], ['readonly' => true]);

        $this->abilities->register('get-scan-report', [
            'label' => \__('Get the report of the last scan', 'italy-cookie-choices'),
            'description' => \implode(' ', [
                \__(
                    'Returns the last scheduled scan: the vendors not blocked and the unknown third parties.',
                    'italy-cookie-choices'
                ),
                \__('Null when no scan has run.', 'italy-cookie-choices'),
            ]),
            'output_schema' => [
                'type' => ['object', 'null'],
                'properties' => [
                    Report::DATE => ['type' => 'string', 'format' => 'date-time'],
                    Report::PAGES => $count,
                    Report::ERRORS => $count,
                    Report::VENDORS => $texts,
                    Report::HOSTS => $texts,
                ],
            ],
            'execute_callback' => $this->report(...),
        ], ['readonly' => true]);
    }

    /**
     * @param array{
     *     home?: bool,
     *     postTypes?: list<string>,
     *     categories?: list<int>,
     *     posts?: list<int>,
     *     offset?: int
     * }|null $input
     * @return array{total: int, truncated: bool, nextOffset: int|null, pages: list<array<string, mixed>>}
     */
    private function scan(?array $input = null): array
    {
        $input ??= [];
        $targets = $this->targets->of(new Selection(
            $input[Selection::HOME] ?? false,
            $input[Selection::POST_TYPES] ?? [],
            $input[Selection::CATEGORIES] ?? [],
            $input[Selection::POSTS] ?? []
        ));
        $ids = \array_column($targets->targets(), 'id');
        $offset = $input[self::OFFSET] ?? 0;
        $next = $offset + ScanEndpoint::BATCH;

        return [
            'total' => \count($ids),
            'truncated' => $targets->isTruncated(),
            'nextOffset' => $next < \count($ids) ? $next : null,
            'pages' => $this->scanner->scan(\array_slice($ids, $offset, ScanEndpoint::BATCH)),
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

        return [
            Report::DATE => $report->date->format(\DateTimeInterface::ATOM),
            Report::PAGES => $report->pages,
            Report::ERRORS => $report->errors,
            Report::VENDORS => $report->vendors,
            Report::HOSTS => $report->hosts,
        ];
    }
}
