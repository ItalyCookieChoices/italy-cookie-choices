<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Application;

/**
 * The languages of a site translated by a multilingual plugin of a single site, like WPML or Polylang:
 * its languages, the translations of the posts and of the strings registered by the plugin.
 */
interface LanguageTranslations
{
    /**
     * @return list<array{code: string, name: string, default: bool}>
     */
    public function languages(): array;

    /**
     * The translation of a post in a language, 0 when there is none.
     */
    public function translatedPost(int $postId, string $language): int;

    /**
     * The translation of a registered string in a language, the original when it is not translated.
     */
    public function translatedString(string $name, string $original, string $language): string;

    /**
     * @throws \RuntimeException When the translation cannot be saved.
     */
    public function saveStringTranslation(string $name, string $original, string $language, string $translation): void;
}
