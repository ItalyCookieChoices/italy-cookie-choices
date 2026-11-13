<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Infrastructure;

use Inpsyde\MultilingualPress\Framework\Api\SiteRelations;
use ItalyCookieChoices\Multilingual\Application\ConnectedSites;

/**
 * The sites connected by MultilingualPress, through its public API. Without MultilingualPress no site is connected.
 */
final readonly class MultilingualPressSites implements ConnectedSites
{
    private const LANGUAGE = 'Inpsyde\\MultilingualPress\\siteLanguageTag';
    private const TRANSLATIONS = 'Inpsyde\\MultilingualPress\\translationIds';
    private const RESOLVE = 'Inpsyde\\MultilingualPress\\resolve';

    public function of(int $siteId): array
    {
        if (!\function_exists(self::RESOLVE) || !\interface_exists(SiteRelations::class)) {
            return [];
        }

        try {
            $relations = \Inpsyde\MultilingualPress\resolve(SiteRelations::class);
        } catch (\Throwable) {
            return [];
        }

        return $relations instanceof SiteRelations
            ? \array_values(\array_map($this->siteId(...), $relations->relatedSiteIds($siteId)))
            : [];
    }

    public function language(int $siteId): string
    {
        if (!\function_exists(self::LANGUAGE)) {
            return '';
        }

        try {
            return \Inpsyde\MultilingualPress\siteLanguageTag($siteId);
        } catch (\Throwable) {
            return '';
        }
    }

    public function translatedPage(int $sourceSiteId, int $pageId, int $targetSiteId): int
    {
        if ($pageId === 0 || !\function_exists(self::TRANSLATIONS)) {
            return 0;
        }

        try {
            $translations = \Inpsyde\MultilingualPress\translationIds($pageId, 'post', $sourceSiteId);
        } catch (\Throwable) {
            return 0;
        }

        return (int) ($translations[$targetSiteId] ?? 0);
    }

    private function siteId(mixed $siteId): int
    {
        return \is_numeric($siteId) ? (int) $siteId : 0;
    }
}
