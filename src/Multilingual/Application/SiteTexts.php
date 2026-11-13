<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Application;

use ItalyCookieChoices\Settings\Schema;

/**
 * The texts of the sites connected as translations: read, saved, and a draft filled from a connected site.
 *
 * The draft fills only the empty texts, the admin reviews it before saving. The policy link is never filled:
 * the policy can change between countries, PolicySuggestion proposes its translation.
 */
final readonly class SiteTexts
{
    private const POLICY_LINK = 'url';
    private const CATEGORIES = 'categories';
    private const CATEGORY_TEXTS = ['label', 'description'];

    public function __construct(
        private ConnectedSites $sites,
        private Translator $translator,
    ) {
    }

    /**
     * @return array<string, mixed> The texts of Schema::SITE_TEXTS, empty when they are not written.
     */
    public function of(int $siteId): array
    {
        $option = $this->optionOf($siteId);
        $texts = [];
        foreach (Schema::SITE_TEXTS as $name) {
            $texts[$name] = $option[$name] ?? ($name === self::CATEGORIES ? [] : '');
        }

        return $texts;
    }

    /**
     * Saves the texts of the site through the sanitizer of the option, its other settings stay.
     *
     * @param array<array-key, mixed> $texts
     * @return array<string, mixed> The saved texts.
     */
    public function save(int $siteId, array $texts): array
    {
        \switch_to_blog($siteId);
        $option = \get_option(Schema::OPTION);
        \update_option(Schema::OPTION, [
            ...(\is_array($option) ? $option : []),
            ...\array_intersect_key($texts, \array_flip(Schema::SITE_TEXTS)),
        ]);
        \restore_current_blog();

        return $this->of($siteId);
    }

    public function hasOwnTexts(int $siteId): bool
    {
        $texts = $this->of($siteId);
        unset($texts[self::POLICY_LINK]);

        return \array_filter($texts, static fn(mixed $text): bool => $text !== '' && $text !== []) !== [];
    }

    /**
     * The texts of the target with the empty ones filled from a connected site, not saved.
     *
     * @param bool $translate Whether the texts are translated in the language of the target.
     * @return array<string, mixed>
     * @throws \InvalidArgumentException When the source is not connected, or no service translates the texts.
     */
    public function draft(int $targetSiteId, int $sourceSiteId, bool $translate): array
    {
        if (!\in_array($sourceSiteId, $this->sites->of($targetSiteId), true)) {
            throw new \InvalidArgumentException(
                \__('The texts come only from a connected site.', 'italy-cookie-choices')
            );
        }

        $from = $this->sites->language($sourceSiteId);
        $to = $this->sites->language($targetSiteId);
        if ($translate && !$this->translator->canTranslate($from, $to)) {
            throw new \InvalidArgumentException(
                \__('No translation service translates between the languages of the sites.', 'italy-cookie-choices')
            );
        }

        $convert = $translate
            ? fn(string $text): string => $this->translator->translate($from, $to, $text)
            : static fn(string $text): string => $text;

        $draft = $this->of($targetSiteId);
        foreach ($this->of($sourceSiteId) as $name => $text) {
            $draft[$name] = $this->filled($name, $draft[$name], $text, $convert);
        }

        return $draft;
    }

    /**
     * @param \Closure(string): string $convert
     */
    private function filled(string $name, mixed $own, mixed $source, \Closure $convert): mixed
    {
        if ($name === self::POLICY_LINK) {
            return $own;
        }

        if ($name === self::CATEGORIES) {
            return $this->filledCategories(\is_array($own) ? $own : [], \is_array($source) ? $source : [], $convert);
        }

        return $own === '' && \is_string($source) && $source !== '' ? $convert($source) : $own;
    }

    /**
     * @param array<array-key, mixed> $own
     * @param array<array-key, mixed> $source
     * @param \Closure(string): string $convert
     * @return array<array-key, mixed>
     */
    private function filledCategories(array $own, array $source, \Closure $convert): array
    {
        foreach ($source as $id => $texts) {
            $ownTexts = \is_array($own[$id] ?? null) ? $own[$id] : [];
            foreach (self::CATEGORY_TEXTS as $field) {
                $text = \is_array($texts) ? $texts[$field] ?? '' : '';
                if (($ownTexts[$field] ?? '') !== '' || !\is_string($text) || $text === '') {
                    continue;
                }

                $ownTexts[$field] = $convert($text);
            }

            $own[$id] = $ownTexts;
        }

        return $own;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function optionOf(int $siteId): array
    {
        \switch_to_blog($siteId);
        $option = \get_option(Schema::OPTION);
        \restore_current_blog();

        return \is_array($option) ? $option : [];
    }
}
