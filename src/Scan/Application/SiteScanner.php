<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

use ItalyCookieChoices\Blocking\Application\VendorsFactory;

/**
 * Scans a batch of pages of the site: the settings page and WP-CLI send the batches one after the other,
 * so a large site does not reach the time limit of a request.
 */
final readonly class SiteScanner
{
    public function __construct(
        private ScanTargets $targets,
        private PageFetcher $fetcher,
        private PageAnalyzer $analyzer,
        private VendorsFactory $vendors,
    ) {
    }

    /**
     * @param list<int> $ids The pages, 0 for the home page. The pages not published are skipped.
     * @return list<array{id: int, url: string, vendors: list<string>, hosts: list<string>, error: string}>
     */
    public function scan(array $ids): array
    {
        $vendors = $this->vendors->known();
        $siteHost = (string) \wp_parse_url(\home_url(), \PHP_URL_HOST);
        $pages = [];
        foreach ($ids as $id) {
            $url = $this->targets->urlOf($id);
            if ($url === '') {
                continue;
            }

            try {
                $findings = $this->analyzer->analyze($this->fetcher->fetch($url), $vendors, $siteHost);
                $pages[] = $this->page($id, $url, $findings->vendors(), $findings->hosts(), '');
            } catch (\RuntimeException $exception) {
                $pages[] = $this->page($id, $url, [], [], $exception->getMessage());
            }
        }

        return $pages;
    }

    /**
     * @param list<string> $vendors
     * @param list<string> $hosts
     * @return array{id: int, url: string, vendors: list<string>, hosts: list<string>, error: string}
     */
    private function page(int $id, string $url, array $vendors, array $hosts, string $error): array
    {
        return ['id' => $id, 'url' => $url, 'vendors' => $vendors, 'hosts' => $hosts, 'error' => $error];
    }
}
