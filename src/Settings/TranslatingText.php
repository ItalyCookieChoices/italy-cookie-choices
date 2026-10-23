<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

/**
 * Dispatched when a saved text is printed, listeners translate it with a multilingual plugin
 * that WPML and Polylang do not cover, like qTranslate-XT:
 *
 * add_action( TranslatingText::class, static function ( TranslatingText $event ): void {
 *     $event->changeText( qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $event->text() ) );
 * } );
 */
final class TranslatingText
{
    /**
     * @param string $name The name of the string, like "Banner text".
     */
    public function __construct(
        private readonly string $name,
        private string $text,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function changeText(string $text): void
    {
        $this->text = $text;
    }
}
