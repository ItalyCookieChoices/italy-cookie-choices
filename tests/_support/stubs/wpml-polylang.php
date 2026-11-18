<?php

/**
 * The part of the APIs of WPML String Translation and Polylang the plugin uses to read and save the translation
 * of a string, for the static analysis and the tests. Loaded only by the tests that need it.
 *
 * WPML: the strings are $GLOBALS['icc_wpml_string_ids'] ("context:name" => id), the saved translations
 * $GLOBALS['icc_wpml_saved'].
 * Polylang: the languages are $GLOBALS['icc_pll_languages'] (slug => name, the first is the default), the posts
 * $GLOBALS['icc_pll_posts'] ("post:slug" => post), the strings $GLOBALS['icc_pll_mo'] (slug => original => text).
 *
 * @see \ItalyCookieChoices\Multilingual\Infrastructure\WpmlTranslations
 * @see \ItalyCookieChoices\Multilingual\Infrastructure\PolylangTranslations
 *
 * @phpcs:disable
 */

declare(strict_types=1);

if (!\function_exists('icl_get_string_id')) {
    function icl_get_string_id(string $string, string $context, string $name = ''): ?int
    {
        $ids = \is_array($GLOBALS['icc_wpml_string_ids'] ?? null) ? $GLOBALS['icc_wpml_string_ids'] : [];
        $id = $ids[$context . ':' . $name] ?? null;

        return \is_int($id) ? $id : null;
    }
}

if (!\function_exists('icl_add_string_translation')) {
    function icl_add_string_translation(int $string_id, string $language, string $value, int $status = 10): int
    {
        $saved = \is_array($GLOBALS['icc_wpml_saved'] ?? null) ? $GLOBALS['icc_wpml_saved'] : [];
        $saved[] = [$string_id, $language, $value, $status];
        $GLOBALS['icc_wpml_saved'] = $saved;

        return \count($saved);
    }
}

if (!\function_exists('pll_languages_list')) {
    /**
     * @param array<string, mixed> $args
     * @return list<string>
     */
    function pll_languages_list(array $args = []): array
    {
        $languages = \is_array($GLOBALS['icc_pll_languages'] ?? null) ? $GLOBALS['icc_pll_languages'] : [];

        $values = ($args['fields'] ?? 'slug') === 'name' ? \array_values($languages) : \array_keys($languages);

        return \array_map(static fn(mixed $value): string => \is_scalar($value) ? (string) $value : '', $values);
    }
}

if (!\function_exists('pll_default_language')) {
    function pll_default_language(string $field = 'slug'): string|false
    {
        return \pll_languages_list(['fields' => $field])[0] ?? false;
    }
}

if (!\function_exists('pll_get_post')) {
    function pll_get_post(int $post_id, string $lang = ''): int|false
    {
        $posts = \is_array($GLOBALS['icc_pll_posts'] ?? null) ? $GLOBALS['icc_pll_posts'] : [];
        $post = $posts[$post_id . ':' . $lang] ?? false;

        return \is_int($post) ? $post : false;
    }
}

if (!\function_exists('pll_translate_string')) {
    function pll_translate_string(string $string, string $lang): string
    {
        $mo = \is_array($GLOBALS['icc_pll_mo'] ?? null) ? $GLOBALS['icc_pll_mo'] : [];
        $translations = \is_array($mo[$lang] ?? null) ? $mo[$lang] : [];
        $translation = $translations[$string] ?? $string;

        return \is_string($translation) ? $translation : $string;
    }
}

if (!\class_exists('PLL_Language')) {
    class PLL_Language
    {
        public function __construct(
            public string $slug,
        ) {
        }
    }
}

if (!\class_exists('PLL_Model')) {
    class PLL_Model
    {
        public function get_language(string $value): PLL_Language|false
        {
            return \in_array($value, \pll_languages_list(), true) ? new PLL_Language($value) : false;
        }
    }
}

if (!\class_exists('PLL_Base')) {
    class PLL_Base
    {
        public PLL_Model $model;

        public function __construct()
        {
            $this->model = new PLL_Model();
        }
    }
}

if (!\function_exists('PLL')) {
    function PLL(): PLL_Base
    {
        return new PLL_Base();
    }
}

if (!\class_exists('PLL_MO')) {
    class PLL_MO
    {
        /** @var array<string, string> */
        private array $entries = [];

        public function import_from_db(PLL_Language $lang): void
        {
            $mo = \is_array($GLOBALS['icc_pll_mo'] ?? null) ? $GLOBALS['icc_pll_mo'] : [];
            $entries = \is_array($mo[$lang->slug] ?? null) ? $mo[$lang->slug] : [];
            $this->entries = [];
            foreach ($entries as $original => $translation) {
                $this->entries[(string) $original] = \is_string($translation) ? $translation : '';
            }
        }

        /**
         * @return array{0: string, 1: string}
         */
        public function make_entry(string $original, string $translation): array
        {
            return [$original, $translation];
        }

        /**
         * @param array{0: string, 1: string} $entry
         */
        public function add_entry(array $entry): bool
        {
            $this->entries[$entry[0]] = $entry[1];

            return true;
        }

        public function export_to_db(PLL_Language $lang): void
        {
            $mo = \is_array($GLOBALS['icc_pll_mo'] ?? null) ? $GLOBALS['icc_pll_mo'] : [];
            $mo[$lang->slug] = $this->entries;
            $GLOBALS['icc_pll_mo'] = $mo;
        }
    }
}
