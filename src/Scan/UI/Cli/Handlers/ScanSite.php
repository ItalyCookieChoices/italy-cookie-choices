<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI\Cli\Handlers;

use ItalyCookieChoices\Blocking\Application\VendorSettings;
use ItalyCookieChoices\Console\IO\IOInterface;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Console\Pipeline\StatusCode;
use ItalyCookieChoices\Scan\Application\SiteScanner;
use ItalyCookieChoices\Scan\UI\Cli\Middlewares\ResolveTargets;
use ItalyCookieChoices\Scan\UI\ScanEndpoint;
use ItalyStrap\Pipeline\HandlerInterface;

/**
 * Scans the pages in batches and lists the vendors found, with the pages where they are,
 * then the hosts of the unknown third parties.
 */
final readonly class ScanSite implements HandlerInterface
{
    private const FIELDS = ['vendor', 'name', 'category', 'enabled', 'pages'];

    public function __construct(
        private SiteScanner $scanner,
        private VendorSettings $vendors,
    ) {
    }

    public function handle(object $message): StatusCode
    {
        \assert($message instanceof Message);
        $io = $message->io();
        $ids = $message->get(ResolveTargets::IDS);
        $ids = \is_array($ids) ? \array_values(\array_filter($ids, \is_int(...))) : [];

        $vendors = [];
        $hosts = [];
        $googleFonts = 0;
        $scanned = 0;
        foreach (\array_chunk($ids, ScanEndpoint::BATCH) as $batch) {
            foreach ($this->scanner->scan($batch) as $page) {
                $this->count($vendors, $page['vendors']);
                $this->count($hosts, $page['hosts']);
                $googleFonts += $page['googleFonts'] ? 1 : 0;
                $this->reportError($io, $page['url'], $page['error']);
            }

            $scanned += \count($batch);
            $io->line(\sprintf(
                /* translators: 1: the pages scanned, 2: the pages to scan. */
                \__('Scanned %1$d of %2$d pages.', 'italy-cookie-choices'),
                $scanned,
                \count($ids)
            ));
        }

        $io->items([...$this->vendorItems($vendors), ...$this->hostItems($hosts)], self::FIELDS);
        if ($googleFonts > 0) {
            $io->line(\sprintf(
                /* translators: %d: the number of pages. */
                \_n(
                    '%d page loads the Google Fonts from Google: serve them from the site.',
                    '%d pages load the Google Fonts from Google: serve them from the site.',
                    $googleFonts,
                    'italy-cookie-choices'
                ),
                $googleFonts
            ));
        }

        return StatusCode::Success;
    }

    /**
     * @param array<string, int> $counts
     * @param list<string> $found
     */
    private function count(array &$counts, array $found): void
    {
        foreach ($found as $key) {
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
    }

    private function reportError(IOInterface $io, string $url, string $error): void
    {
        if ($error === '') {
            return;
        }

        $io->error(\sprintf(
            /* translators: 1: the address of the page, 2: the reason. */
            \__('The page %1$s was not scanned: %2$s', 'italy-cookie-choices'),
            $url,
            $error
        ));
    }

    /**
     * @param array<string, int> $found
     * @return list<array<string, string>>
     */
    private function vendorItems(array $found): array
    {
        $items = [];
        foreach ($this->vendors->all() as $vendor) {
            if (!\array_key_exists($vendor['id'], $found)) {
                continue;
            }

            $items[] = [
                'vendor' => $vendor['id'],
                'name' => $vendor['name'],
                'category' => $vendor['category'],
                'enabled' => $vendor['enabled'] ? 'on' : 'off',
                'pages' => (string) $found[$vendor['id']],
            ];
        }

        return $items;
    }

    /**
     * @param array<string, int> $found
     * @return list<array<string, string>>
     */
    private function hostItems(array $found): array
    {
        $items = [];
        foreach ($found as $host => $pages) {
            $items[] = ['vendor' => $host, 'name' => '', 'category' => '', 'enabled' => '', 'pages' => (string) $pages];
        }

        return $items;
    }
}
