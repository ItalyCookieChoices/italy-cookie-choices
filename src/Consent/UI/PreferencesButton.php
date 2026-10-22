<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

/**
 * The button that opens the preferences, printed by the shortcode and by the block.
 */
final readonly class PreferencesButton
{
    public const CLASS_NAME = 'icc-preferences-button';

    /**
     * @param string $text The label, the default one when it is empty.
     * @param string $attributes The attributes of the button, already escaped.
     */
    public function html(string $text, string $attributes = 'class="' . self::CLASS_NAME . '"'): string
    {
        return \sprintf(
            '<button type="button" %s data-icc-open-preferences>%s</button>',
            $attributes,
            \esc_html(\trim($text) !== '' ? $text : \__('Cookie preferences', 'italy-cookie-choices'))
        );
    }
}
