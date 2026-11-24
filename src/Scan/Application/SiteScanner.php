<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Scan\Domain\Findings;

/**
 * Scans a batch of pages of the site: the settings page and WP-CLI send the batches one after the other,
 * so a large site does not reach the time limit of a request.
 *
 * @phpstan-type Page array{
 *     id: int,
 *     url: string,
 *     vendors: list<string>,
 *     hosts: list<string>,
 *     googleFonts: bool,
 *     error: string
 * }
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
     * @return list<Page>
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
                $pages[] = $this->page($id, $url, $findings, '');
            } catch (\RuntimeException $exception) {
                $pages[] = $this->page($id, $url, new Findings([], []), $exception->getMessage());
            }
        }

        return $pages;
    }

    /**
     * @return Page
     */
    private function page(int $id, string $url, Findings $findings, string $error): array
    {
        return [
            'id' => $id,
            'url' => $url,
            'vendors' => $findings->vendors(),
            'hosts' => $findings->hosts(),
            'googleFonts' => $findings->loadsGoogleFonts(),
            'error' => $error,
        ];
    }
}
