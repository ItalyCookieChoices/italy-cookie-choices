<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Application;

use ItalyCookieChoices\Settings\Schema;

/**
 * The policy link in each language of WPML or Polylang: its current translation, and the translation
 * of the policy page proposed to the admin. The admin chooses, the policy can change between countries.
 *
 * The policy link is the "Banner url" string the plugin registers in WPML and Polylang.
 */
final readonly class PolicyTranslations
{
    public const STRING_NAME = 'Banner url';
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
    public function rows(): array
    {
        $url = $this->policyLink();
        $page = $this->policyPage($url);
        $rows = [];
        foreach ($this->translations->languages() as $language) {
            if ($language['default']) {
                continue;
            }

            $current = $url === ''
                ? ''
                : $this->translations->translatedString(self::STRING_NAME, $url, $language['code']);
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
     * Saves the proposal as the translation of the policy link in the language.
     *
     * @return list<array{language: string, name: string, current: string, proposal: string}>
     * @throws \InvalidArgumentException When the language has no proposal, or the banner has no policy link.
     * @throws \RuntimeException When the multilingual plugin does not save the translation.
     */
    public function use(string $language): array
    {
        $proposal = \array_column($this->rows(), 'proposal', 'language')[$language] ?? '';
        $url = $this->policyLink();
        if ($proposal === '' || $url === '') {
            throw new \InvalidArgumentException(
                \__('There is no translation of the policy page in this language.', 'italy-cookie-choices')
            );
        }

        $this->translations->saveStringTranslation(self::STRING_NAME, $url, $language, $proposal);

        return $this->rows();
    }

    private function policyLink(): string
    {
        $option = \get_option(Schema::OPTION);

        return \is_array($option) && \is_string($option['url'] ?? null) ? $option['url'] : '';
    }

    private function policyPage(string $url): int
    {
        $page = $url === '' ? 0 : \url_to_postid($url);
        if ($page > 0) {
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
