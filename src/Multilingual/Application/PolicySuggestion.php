<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Application;

use ItalyCookieChoices\Settings\Schema;

/**
 * The policy link proposed to a site: the translation, published on the site, of the page
 * a connected site links in its banner, or of its privacy page.
 *
 * It is only a proposal, the admin chooses: the policy can change between countries.
 */
final readonly class PolicySuggestion
{
    private const PRIVACY_PAGE = 'wp_page_for_privacy_policy';

    public function __construct(
        private ConnectedSites $sites,
    ) {
    }

    /**
     * @return string The URL of the translated page, empty when there is none.
     */
    public function for(int $siteId): string
    {
        foreach ($this->sites->of($siteId) as $connected) {
            $translation = $this->sites->translatedPage($connected, $this->policyPageOf($connected), $siteId);
            $url = $translation > 0 ? $this->publishedUrl($siteId, $translation) : '';
            if ($url !== '') {
                return $url;
            }
        }

        return '';
    }

    private function policyPageOf(int $siteId): int
    {
        \switch_to_blog($siteId);
        $option = \get_option(Schema::OPTION);
        $url = \is_array($option) && \is_string($option['url'] ?? null) ? $option['url'] : '';
        $pageId = $url === '' ? 0 : \url_to_postid($url);
        $privacyPage = \get_option(self::PRIVACY_PAGE);
        \restore_current_blog();

        return $pageId > 0 ? $pageId : (\is_numeric($privacyPage) ? (int) $privacyPage : 0);
    }

    private function publishedUrl(int $siteId, int $pageId): string
    {
        \switch_to_blog($siteId);
        $url = \get_post_status($pageId) === 'publish' ? (string) \get_permalink($pageId) : '';
        \restore_current_blog();

        return $url;
    }
}
