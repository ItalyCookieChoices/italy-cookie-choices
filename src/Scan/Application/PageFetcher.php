<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

/**
 * Reads the HTML of a page of the site, as a visitor that has not chosen yet.
 */
interface PageFetcher
{
    /**
     * @throws \RuntimeException When the page cannot be read, with the reason.
     */
    public function fetch(string $url): string;
}
