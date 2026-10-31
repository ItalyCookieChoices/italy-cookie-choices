<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Legacy;

use ItalyStrap\Event\SubscriberInterface;

/**
 * Warns the developers who still filter the hooks of the 2.x versions that no longer have an effect.
 */
final readonly class DeprecatedHooks implements SubscriberInterface
{
    public function getSubscribedEvents(): iterable
    {
        yield 'init' => 'warn';
    }

    public function warn(): void
    {
        // The 2.x versions did not print the [accept_button] shortcode for the bots, the button needs JavaScript.
        \apply_filters_deprecated(
            'icc_bots',
            [[]],
            '3.0.0',
            '',
            \esc_html__('The bots are no longer detected, they do not run the consent scripts.', 'italy-cookie-choices')
        );
    }
}
