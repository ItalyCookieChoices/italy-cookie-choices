<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Application;

/**
 * The automatic translation of the texts, given by a multilingual plugin with a translation service set up.
 */
interface Translator
{
    /**
     * Whether a translation service translates between the two languages, as BCP 47 tags.
     */
    public function canTranslate(string $sourceLanguage, string $targetLanguage): bool;

    /**
     * @throws \RuntimeException When the text is not translated.
     */
    public function translate(string $sourceLanguage, string $targetLanguage, string $text): string;
}
