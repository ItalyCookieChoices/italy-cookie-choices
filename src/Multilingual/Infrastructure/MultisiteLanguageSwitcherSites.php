<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Infrastructure;

/**
 * The sites connected by Multisite Language Switcher, through its public API: the sites of its collection,
 * with their locale, and the translations of the posts stored by the plugin. It has no automatic translation.
 */
final readonly class MultisiteLanguageSwitcherSites implements PluginConnectedSites
{
    private const COLLECTION = 'msls_blog_collection';
    private const POST = 'msls_get_post';

    public function isActive(): bool
    {
        return \function_exists(self::COLLECTION) && \function_exists(self::POST);
    }

    public function of(int $siteId): array
    {
        $locales = $this->locales();
        if (!\array_key_exists($siteId, $locales)) {
            return [];
        }

        return \array_values(\array_filter(
            \array_keys($locales),
            static fn(int $connected): bool => $connected !== $siteId
        ));
    }

    public function language(int $siteId): string
    {
        return \str_replace('_', '-', $this->locales()[$siteId] ?? '');
    }

    public function translatedPage(int $sourceSiteId, int $pageId, int $targetSiteId): int
    {
        $locale = $this->locales()[$targetSiteId] ?? '';
        if ($pageId === 0 || $locale === '' || !$this->isActive()) {
            return 0;
        }

        \switch_to_blog($sourceSiteId);
        try {
            $translations = \msls_get_post($pageId);
            $translation = $translations->has_value($locale) ? $translations->{$locale} : 0;
        } catch (\Throwable) {
            $translation = 0;
        } finally {
            \restore_current_blog();
        }

        return \is_numeric($translation) ? (int) $translation : 0;
    }

    /**
     * The locales of the connected sites, like de_DE, by site.
     *
     * @return array<int, string>
     */
    private function locales(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        try {
            $blogs = \msls_blog_collection()->get_objects();
        } catch (\Throwable) {
            return [];
        }

        $locales = [];
        foreach ($blogs as $blog) {
            $locales[(int) $blog->userblog_id] = (string) $blog->get_language('');
        }

        return $locales;
    }
}
