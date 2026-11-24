<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Scan\Application\PageAnalyzer;
use ItalyCookieChoices\Scan\Application\ScanTargets;
use ItalyCookieChoices\Scan\Application\SiteScanner;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Scan\FakePageFetcher;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SiteScannerTest extends WPTestCase
{
    use BuildsContainer;

    public function testItScansThePagesAndTellsWhyAPageFails(): void
    {
        $page = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $missing = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $this->assertIsInt($page);
        $this->assertIsInt($missing);
        $fetcher = new FakePageFetcher([
            \home_url('/') => '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter">'
                . '<script src="https://static.hotjar.com/c/hotjar.js"></script>',
            (string) \get_permalink($page) => '<iframe src="https://forms.example.net/embed"></iframe>',
        ]);

        $pages = $this->makeSut($fetcher)->scan([0, $page, $missing]);

        $this->assertSame(
            [
                [
                    'id' => 0,
                    'url' => \home_url('/'),
                    'vendors' => ['hotjar'],
                    'hosts' => [],
                    'googleFonts' => true,
                    'error' => '',
                ],
                [
                    'id' => $page,
                    'url' => (string) \get_permalink($page),
                    'vendors' => [],
                    'hosts' => ['forms.example.net'],
                    'googleFonts' => false,
                    'error' => '',
                ],
                [
                    'id' => $missing,
                    'url' => (string) \get_permalink($missing),
                    'vendors' => [],
                    'hosts' => [],
                    'googleFonts' => false,
                    'error' => 'The page answered with the status 404.',
                ],
            ],
            $pages
        );
    }

    public function testThePagesThatAreNotPublishedAreNotFetched(): void
    {
        $draft = self::factory()->post->create(['post_status' => 'draft']);
        $this->assertIsInt($draft);
        $fetcher = new FakePageFetcher();

        $this->assertSame([], $this->makeSut($fetcher)->scan([$draft, 99999]));
        $this->assertSame([], $fetcher->fetched);
    }

    private function makeSut(FakePageFetcher $fetcher): SiteScanner
    {
        return new SiteScanner(
            new ScanTargets(),
            $fetcher,
            new PageAnalyzer(),
            $this->service(VendorsFactory::class)
        );
    }
}
