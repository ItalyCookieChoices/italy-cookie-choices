<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Infrastructure;

use ItalyCookieChoices\Settings\StringTranslations;
use ItalyStrap\Event\GlobalDispatcherInterface;

/**
 * The languages of WPML: its filters for the languages and the translations, the functions of
 * WPML String Translation to save the translation of a string.
 */
final readonly class WpmlTranslations implements PluginLanguageTranslations
{
    /**
     * The status of a complete translation, ICL_TM_COMPLETE.
     */
    private const COMPLETE = 10;

    public function __construct(
        private GlobalDispatcherInterface $dispatcher,
    ) {
    }

    public function isActive(): bool
    {
        return \has_filter('wpml_active_languages') !== false;
    }

    public function languages(): array
    {
        $languages = $this->dispatcher->filter('wpml_active_languages', null, ['skip_missing' => 0]);
        $default = $this->dispatcher->filter('wpml_default_language', null);
        $list = [];
        foreach (\is_array($languages) ? $languages : [] as $code => $language) {
            $list[] = [
                'code' => (string) $code,
                'name' => $this->nameOf($language, (string) $code),
                'default' => $code === $default,
            ];
        }

        return $list;
    }

    private function nameOf(mixed $language, string $code): string
    {
        $name = \is_array($language) ? $language['native_name'] ?? $language['translated_name'] ?? null : null;

        return \is_string($name) && $name !== '' ? $name : $code;
    }

    public function translatedPost(int $postId, string $language): int
    {
        $translation = $this->dispatcher->filter('wpml_object_id', $postId, \get_post_type($postId), false, $language);

        return \is_numeric($translation) ? (int) $translation : 0;
    }

    public function translatedString(string $name, string $original, string $language): string
    {
        $translation = $this->dispatcher->filter(
            'wpml_translate_single_string',
            $original,
            StringTranslations::CONTEXT,
            $name,
            $language
        );

        return \is_string($translation) ? $translation : $original;
    }

    public function saveStringTranslation(string $name, string $original, string $language, string $translation): void
    {
        if (!\function_exists('icl_get_string_id') || !\function_exists('icl_add_string_translation')) {
            throw new \RuntimeException(\__('Activate WPML String Translation to save it.', 'italy-cookie-choices'));
        }

        $stringId = (int) \icl_get_string_id($original, StringTranslations::CONTEXT, $name);
        if ($stringId === 0) {
            throw new \RuntimeException(\__(
                'The policy link is not registered in WPML yet, open the dashboard and try again.',
                'italy-cookie-choices'
            ));
        }

        \icl_add_string_translation($stringId, $language, $translation, self::COMPLETE);
    }
}
