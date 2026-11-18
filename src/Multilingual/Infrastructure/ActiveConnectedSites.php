<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Infrastructure;

use ItalyCookieChoices\Multilingual\Application\ConnectedSites;

/**
 * The connected sites of the first multilingual plugin that is active: MultilingualPress first,
 * then Multisite Language Switcher. Without them no site is connected.
 */
final readonly class ActiveConnectedSites implements ConnectedSites
{
    /**
     * @var list<PluginConnectedSites>
     */
    private array $plugins;

    public function __construct(
        MultilingualPressSites $multilingualPress,
        MultisiteLanguageSwitcherSites $multisiteLanguageSwitcher,
    ) {
        $this->plugins = [$multilingualPress, $multisiteLanguageSwitcher];
    }

    public function of(int $siteId): array
    {
        return $this->active()?->of($siteId) ?? [];
    }

    public function language(int $siteId): string
    {
        return $this->active()?->language($siteId) ?? '';
    }

    public function translatedPage(int $sourceSiteId, int $pageId, int $targetSiteId): int
    {
        return $this->active()?->translatedPage($sourceSiteId, $pageId, $targetSiteId) ?? 0;
    }

    private function active(): ?PluginConnectedSites
    {
        foreach ($this->plugins as $plugin) {
            if ($plugin->isActive()) {
                return $plugin;
            }
        }

        return null;
    }
}
