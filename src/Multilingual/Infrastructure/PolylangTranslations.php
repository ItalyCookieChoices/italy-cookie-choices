<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Infrastructure;

/**
 * The languages of Polylang: its public API for the languages and the translations, and its PLL_MO
 * to save the translation of a string, like its page of the string translations does.
 */
final readonly class PolylangTranslations implements PluginLanguageTranslations
{
    public function isActive(): bool
    {
        return \function_exists('pll_languages_list') && \function_exists('PLL');
    }

    public function languages(): array
    {
        $codes = \pll_languages_list(['fields' => 'slug']);
        $names = \pll_languages_list(['fields' => 'name']);
        $default = \pll_default_language('slug');
        $list = [];
        foreach ($codes as $index => $code) {
            $name = $names[$index] ?? $code;
            $list[] = ['code' => (string) $code, 'name' => (string) $name, 'default' => $code === $default];
        }

        return $list;
    }

    public function translatedPost(int $postId, string $language): int
    {
        $translation = \pll_get_post($postId, $language);

        return \is_int($translation) ? $translation : 0;
    }

    public function translatedString(string $name, string $original, string $language): string
    {
        return \pll_translate_string($original, $language);
    }

    public function saveStringTranslation(string $name, string $original, string $language, string $translation): void
    {
        $polylangLanguage = \PLL()->model->get_language($language);
        if (!$polylangLanguage instanceof \PLL_Language || !\class_exists(\PLL_MO::class)) {
            throw new \RuntimeException(\__('Polylang does not know this language.', 'italy-cookie-choices'));
        }

        $mo = new \PLL_MO();
        $mo->import_from_db($polylangLanguage);
        $mo->add_entry($mo->make_entry($original, $translation));
        $mo->export_to_db($polylangLanguage);
    }
}
