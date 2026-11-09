<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cookies\UI;

use ItalyCookieChoices\Settings\Options;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The cookie table for the cookie policy page, in the classic editor and the page builders:
 *
 * [cookie_table]
 */
final readonly class CookieTableShortcode implements SubscriberInterface
{
    public const TAG = 'cookie_table';

    public function __construct(
        private CookieTableRenderer $renderer,
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

    public function render(): string
    {
        return $this->renderer->html();
    }
}
