<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Multilingual;

use ItalyCookieChoices\Multilingual\Application\Translator;

/**
 * The translation service in the tests: it marks the text with the target language, "[de-DE] text".
 */
final class FakeTranslator implements Translator
{
    /** @var list<string> */
    public array $translated = [];

    /**
     * @param bool $available Whether a translation service is set up.
     */
    public function __construct(
        private readonly bool $available = true,
    ) {
    }

    public function canTranslate(string $sourceLanguage, string $targetLanguage): bool
    {
        return $this->available && $sourceLanguage !== '' && $targetLanguage !== '';
    }

    public function translate(string $sourceLanguage, string $targetLanguage, string $text): string
    {
        $this->translated[] = $text;

        return '[' . $targetLanguage . '] ' . $text;
    }
}
