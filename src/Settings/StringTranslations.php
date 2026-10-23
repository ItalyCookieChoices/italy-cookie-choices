<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

use ItalyStrap\Event\GlobalDispatcherInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Registers the saved texts in WPML and Polylang, and translates them with the listeners of TranslatingText.
 */
final readonly class StringTranslations
{
    public const CONTEXT = 'Italy Cookie Choices';

    public function __construct(
        private GlobalDispatcherInterface $globalDispatcher,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * Polylang lists only the strings registered on the current request.
     */
    public function register(string $name, string $text, bool $multiline = false): void
    {
        $this->globalDispatcher->trigger('wpml_register_single_string', self::CONTEXT, $name, $text);
        if (!\function_exists('pll_register_string')) {
            return;
        }

        \pll_register_string($name, $text, self::CONTEXT, $multiline);
    }

    public function translate(string $name, string $text): string
    {
        $event = new TranslatingText($name, $this->multilingual($name, $text));
        $this->dispatcher->dispatch($event);

        return $event->text();
    }

    /**
     * Polylang finds the translation by the original text, WPML by the name.
     */
    private function multilingual(string $name, string $text): string
    {
        $translated = $this->globalDispatcher->filter('wpml_translate_single_string', $text, self::CONTEXT, $name);
        if (\is_string($translated) && $translated !== $text) {
            return $translated;
        }

        return \function_exists('pll__') ? \pll__($text) : $text;
    }
}
