<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Application;

use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;

/**
 * A link of the banner in each language of WPML or Polylang: its current translation, and the translation
 * of its page proposed to the admin. The admin chooses, the policy can change between countries.
 *
 * The link is the string of its address the plugin registers in WPML and Polylang, like "Banner url".
 */
final readonly class LinkTranslations
{
    /**
     * The page of the privacy policy of WordPress, proposed when the banner has no policy link.
     */
    private const PRIVACY_PAGE = 'wp_page_for_privacy_policy';

    public function __construct(
        private LanguageTranslations $translations,
    ) {
    }

    /**
     * The languages but the default one, where the link of the banner is written.
     *
     * @return list<array{language: string, name: string, current: string, proposal: string}>
     */
    public function rows(BannerLink $link): array
    {
        $url = $this->address($link);
        $page = $this->page($link, $url);
        $rows = [];
        foreach ($this->translations->languages() as $language) {
            if ($language['default']) {
                continue;
            }

            $current = $url === ''
                ? ''
                : $this->translations->translatedString($this->stringName($link), $url, $language['code']);
            $rows[] = [
                'language' => $language['code'],
                'name' => $language['name'],
                'current' => $current === $url ? '' : $current,
                'proposal' => $this->proposal($page, $language['code']),
            ];
        }

        return $rows;
    }

    /**
     * Saves the proposal as the translation of the link in the language.
     *
     * @return list<array{language: string, name: string, current: string, proposal: string}>
     * @throws \InvalidArgumentException When the language has no proposal, or the banner has not the link.
     * @throws \RuntimeException When the multilingual plugin does not save the translation.
     */
    public function use(BannerLink $link, string $language): array
    {
        $proposal = \array_column($this->rows($link), 'proposal', 'language')[$language] ?? '';
        $url = $this->address($link);
        if ($proposal === '' || $url === '') {
            throw new \InvalidArgumentException(
                \__('There is no translation of the page in this language.', 'italy-cookie-choices')
            );
        }

        $this->translations->saveStringTranslation($this->stringName($link), $url, $language, $proposal);

        return $this->rows($link);
    }

    private function stringName(BannerLink $link): string
    {
        return Options::TRANSLATABLE[$link->urlSetting()];
    }

    /**
     * The saved address of the link, not translated.
     */
    private function address(BannerLink $link): string
    {
        $option = \get_option(Schema::OPTION);
        $url = \is_array($option) ? $option[$link->urlSetting()] ?? '' : '';

        return \is_string($url) ? $url : '';
    }

    private function page(BannerLink $link, string $url): int
    {
        $page = $url === '' ? 0 : \url_to_postid($url);
        if ($page > 0 || $link !== BannerLink::Policy) {
            return $page;
        }

        $privacyPage = \get_option(self::PRIVACY_PAGE);

        return \is_numeric($privacyPage) ? (int) $privacyPage : 0;
    }

    private function proposal(int $page, string $language): string
    {
        $translation = $page === 0 ? 0 : $this->translations->translatedPost($page, $language);
        if ($translation === 0 || $translation === $page || \get_post_status($translation) !== 'publish') {
            return '';
        }

        return (string) \get_permalink($translation);
    }
}
