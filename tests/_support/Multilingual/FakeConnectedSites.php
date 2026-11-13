<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Multilingual;

use ItalyCookieChoices\Multilingual\Application\ConnectedSites;

/**
 * The connections of a multilingual plugin in the tests, every site connected to the others of the list.
 */
final class FakeConnectedSites implements ConnectedSites
{
    /** @var array<string, int> "sourceSite:page:targetSite" => translated page */
    private array $translations = [];

    /**
     * @param array<int, string> $languages The connected sites and their languages.
     */
    public function __construct(
        private readonly array $languages = [],
    ) {
    }

    public function of(int $siteId): array
    {
        if (!\array_key_exists($siteId, $this->languages)) {
            return [];
        }

        return \array_values(\array_filter(
            \array_keys($this->languages),
            static fn(int $connected): bool => $connected !== $siteId
        ));
    }

    public function language(int $siteId): string
    {
        return $this->languages[$siteId] ?? '';
    }

    public function translatedPage(int $sourceSiteId, int $pageId, int $targetSiteId): int
    {
        return $this->translations[$sourceSiteId . ':' . $pageId . ':' . $targetSiteId] ?? 0;
    }

    public function translate(int $sourceSiteId, int $pageId, int $targetSiteId, int $translatedPageId): void
    {
        $this->translations[$sourceSiteId . ':' . $pageId . ':' . $targetSiteId] = $translatedPageId;
    }
}
