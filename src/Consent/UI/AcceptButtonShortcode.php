<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Settings\Options;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The [accept_button] shortcode of the 2.x versions, a button that accepts every category:
 *
 * [accept_button text="I agree"]
 *
 * Its text is the accept button text of the settings when the attribute is empty.
 */
final readonly class AcceptButtonShortcode implements SubscriberInterface
{
    public const TAG = 'accept_button';
    public const CLASS_NAME = 'icc-accept-button';

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
    }

    public function render(mixed $attributes = []): string
    {
        $attributes = \shortcode_atts(['text' => ''], \is_array($attributes) ? $attributes : [], self::TAG);
        $text = \trim($attributes['text']);

        return \sprintf(
            '<button type="button" class="%s" data-icc-accept-all>%s</button>',
            self::CLASS_NAME,
            \esc_html($text !== '' ? $text : $this->options->translated(
                'button_text',
                \__('Accept all', 'italy-cookie-choices')
            ))
        );
    }
}
