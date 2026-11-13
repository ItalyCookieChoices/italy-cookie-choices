<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\UI;

use ItalyCookieChoices\Multilingual\Application\ConnectedSites;
use ItalyCookieChoices\Multilingual\Application\Translator;
use ItalyCookieChoices\Settings\UI\SettingsPage;

/**
 * The data of the Languages tab of the settings page: the language of the site and the connected sites
 * the current user administers, empty without a multilingual plugin.
 */
final readonly class LanguagesData
{
    public function __construct(
        private ConnectedSites $sites,
        private Translator $translator,
    ) {
    }

    /**
     * @return array{
     *     siteId: int,
     *     language: string,
     *     sites: list<array{id: int, name: string, url: string, language: string, canTranslate: bool}>
     * }
     */
    public function forSite(int $siteId): array
    {
        $language = $this->sites->language($siteId);
        $sites = [];
        foreach ($this->sites->of($siteId) as $connected) {
            if (!\current_user_can_for_site($connected, SettingsPage::CAPABILITY)) {
                continue;
            }

            $connectedLanguage = $this->sites->language($connected);
            $sites[] = [
                'id' => $connected,
                'name' => $this->name($connected),
                'url' => \get_home_url($connected, '/'),
                'language' => $connectedLanguage,
                'canTranslate' => $this->translator->canTranslate($language, $connectedLanguage),
            ];
        }

        return ['siteId' => $siteId, 'language' => $language, 'sites' => $sites];
    }

    private function name(int $siteId): string
    {
        $name = \get_blog_option($siteId, 'blogname');

        return \html_entity_decode(\is_string($name) ? $name : '', \ENT_QUOTES);
    }
}
