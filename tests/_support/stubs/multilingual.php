<?php

/**
 * Polylang and WPML like stubs, they record the registered strings and translate from a global map.
 * Loaded only by the tests that need them, the functions cannot be undefined.
 */

declare(strict_types=1);

if (!\function_exists('pll_register_string')) {
    function pll_register_string(
        string $name,
        string $string,
        string $context = 'Polylang',
        bool $multiline = false
    ): void {
        $registered = \is_array($GLOBALS['icc_pll_strings'] ?? null) ? $GLOBALS['icc_pll_strings'] : [];
        $registered[] = [$name, $string, $context, $multiline];
        $GLOBALS['icc_pll_strings'] = $registered;
    }
}

if (!\function_exists('pll__')) {
    function pll__(string $string): string
    {
        $translations = \is_array($GLOBALS['icc_pll_translations'] ?? null) ? $GLOBALS['icc_pll_translations'] : [];
        $translation = $translations[$string] ?? $string;

        return \is_string($translation) ? $translation : $string;
    }
}

if (!\function_exists('icl_unregister_string')) {
    function icl_unregister_string(string $context, string $name): void
    {
        $unregistered = \is_array($GLOBALS['icc_wpml_unregistered'] ?? null) ? $GLOBALS['icc_wpml_unregistered'] : [];
        $unregistered[] = [$context, $name];
        $GLOBALS['icc_wpml_unregistered'] = $unregistered;
    }
}
