<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Application;

/**
 * The sites of a multisite network connected as translations of each other, by a multilingual plugin.
 *
 * Without such a plugin no site is connected: the plugin does not store its own links between the sites.
 */
interface ConnectedSites
{
    /**
     * @return list<int> The sites connected to the site, without the site itself.
     */
    public function of(int $siteId): array;

    /**
     * The language of the site as a BCP 47 tag, like de-DE, empty when it is not known.
     */
    public function language(int $siteId): string;

    /**
     * The translation on the target site of a page of the source site, 0 when there is none.
     */
    public function translatedPage(int $sourceSiteId, int $pageId, int $targetSiteId): int;
}
