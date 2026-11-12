<?php

/**
 * The signatures of the MultilingualPress API used by the plugin, for the static analysis only.
 *
 * MultilingualPress is a paid plugin: it is never installed in the tests, its code is not here.
 *
 * @see \ItalyCookieChoices\Multilingual\Infrastructure\MultilingualPressSites
 * @see \ItalyCookieChoices\Multilingual\Infrastructure\MultilingualPressTranslator
 *
 * @phpcs:disable
 */

declare(strict_types=1);

namespace Inpsyde\MultilingualPress {

    /**
     * @return mixed The service of the container of MultilingualPress.
     */
    function resolve(?string $name = null)
    {
    }

    function siteLanguageTag(int $siteId = 0): string
    {
        return '';
    }

    /**
     * @return array<int, int> The translations, keyed by site.
     */
    function translationIds(int $contentId = 0, string $type = 'post', int $siteId = 0): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $translatorOptions
     */
    function translate(
        string $sourceLanguageCode,
        string $targetLanguageCode,
        string $translatorId,
        string $text,
        array $translatorOptions = []
    ): string {
        return $text;
    }
}

namespace Inpsyde\MultilingualPress\Framework\Api {

    interface SiteRelations
    {
        /**
         * @return array<array-key, mixed>
         */
        public function relatedSiteIds(int $siteId, bool $includeSite = false): array;
    }
}

namespace Inpsyde\MultilingualPress\Module\AutomaticTranslation\Translator\Translators {

    interface TranslatorInterface
    {
        public function id(): string;

        public function isLanguageCombinationSupported(string $sourceLanguageCode, string $targetLanguageCode): bool;
    }
}

namespace Inpsyde\MultilingualPress\Module\AutomaticTranslation\Translator\TranslatorManager {

    interface TranslatorManagerInterface
    {
        /**
         * @return array<array-key, mixed>
         */
        public function activeTranslators(): array;
    }
}
