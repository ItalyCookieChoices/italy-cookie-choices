<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Multilingual;

use ItalyCookieChoices\Multilingual\Application\LanguageTranslations;

/**
 * The languages of WPML or Polylang in the tests, the first one is the default.
 */
final class FakeLanguageTranslations implements LanguageTranslations
{
    /** @var array<string, int> "post:language" => translated post */
    private array $posts = [];

    /** @var array<string, string> "name:language" => translation */
    public array $strings = [];

    /**
     * @param array<string, string> $languages The codes and the names of the languages.
     */
    public function __construct(
        private readonly array $languages = [],
    ) {
    }

    public function languages(): array
    {
        $languages = [];
        foreach ($this->languages as $code => $name) {
            $languages[] = ['code' => $code, 'name' => $name, 'default' => $languages === []];
        }

        return $languages;
    }

    public function translatedPost(int $postId, string $language): int
    {
        return $this->posts[$postId . ':' . $language] ?? 0;
    }

    public function translatePost(int $postId, string $language, int $translation): void
    {
        $this->posts[$postId . ':' . $language] = $translation;
    }

    public function translatedString(string $name, string $original, string $language): string
    {
        return $this->strings[$name . ':' . $language] ?? $original;
    }

    public function saveStringTranslation(string $name, string $original, string $language, string $translation): void
    {
        $this->strings[$name . ':' . $language] = $translation;
    }
}
