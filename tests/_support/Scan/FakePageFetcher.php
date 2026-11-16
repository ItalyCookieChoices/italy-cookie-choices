<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Scan;

use ItalyCookieChoices\Scan\Application\PageFetcher;

/**
 * The pages of the site in the tests, by address. A missing page fails like a loopback request.
 */
final class FakePageFetcher implements PageFetcher
{
    /** @var list<string> */
    public array $fetched = [];

    /**
     * @param array<string, string> $pages
     */
    public function __construct(
        private readonly array $pages = [],
    ) {
    }

    public function fetch(string $url): string
    {
        $this->fetched[] = $url;
        if (!\array_key_exists($url, $this->pages)) {
            throw new \RuntimeException('The page answered with the status 404.');
        }

        return $this->pages[$url];
    }
}
