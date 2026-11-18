<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Infrastructure;

use ItalyCookieChoices\Multilingual\Application\LanguageTranslations;

/**
 * The languages of the first multilingual plugin of a single site that is active: WPML first, then Polylang.
 * Without them the site has no languages.
 */
final readonly class ActiveLanguageTranslations implements LanguageTranslations
{
    /**
     * @var list<PluginLanguageTranslations>
     */
    private array $plugins;

    public function __construct(
        WpmlTranslations $wpml,
        PolylangTranslations $polylang,
    ) {
        $this->plugins = [$wpml, $polylang];
    }

    public function languages(): array
    {
        return $this->active()?->languages() ?? [];
    }

    public function translatedPost(int $postId, string $language): int
    {
        return $this->active()?->translatedPost($postId, $language) ?? 0;
    }

    public function translatedString(string $name, string $original, string $language): string
    {
        return $this->active()?->translatedString($name, $original, $language) ?? $original;
    }

    public function saveStringTranslation(string $name, string $original, string $language, string $translation): void
    {
        $plugin = $this->active();
        if ($plugin === null) {
            throw new \RuntimeException(\__('There is no multilingual plugin active.', 'italy-cookie-choices'));
        }

        $plugin->saveStringTranslation($name, $original, $language, $translation);
    }

    private function active(): ?PluginLanguageTranslations
    {
        foreach ($this->plugins as $plugin) {
            if ($plugin->isActive()) {
                return $plugin;
            }
        }

        return null;
    }
}
