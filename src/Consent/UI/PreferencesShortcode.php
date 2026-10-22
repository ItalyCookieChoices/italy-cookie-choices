<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Settings\Options;
use ItalyStrap\Event\SubscriberInterface;

/**
 * A button that opens the preferences, to change or withdraw the consent as easily as it was given:
 *
 * [cookie_preferences text="Change your cookie choice"]
 *
 * It replaces the 2.x [delete_cookie], whose button never worked.
 */
final readonly class PreferencesShortcode implements SubscriberInterface
{
    public const TAG = 'cookie_preferences';
    public const LEGACY_TAG = 'delete_cookie';

    public function __construct(
        private Options $options,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'init' => 'register';
    }

    public function register(): void
    {
        if (!$this->options->isActive()) {
            return;
        }

        \add_shortcode(self::TAG, $this->render(...));
        \add_shortcode(self::LEGACY_TAG, $this->render(...));
    }

    public function render(mixed $attributes = []): string
    {
        $attributes = \shortcode_atts(
            ['text' => \__('Cookie preferences', 'italy-cookie-choices')],
            \is_array($attributes) ? $attributes : [],
            self::TAG
        );

        return \sprintf(
            '<button type="button" class="icc-preferences-button" data-icc-open-preferences>%s</button>',
            \esc_html((string) $attributes['text'])
        );
    }
}
