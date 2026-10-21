<?php

declare(strict_types=1);

namespace ItalyCookieChoices\I18n;

use ItalyStrap\Event\SubscriberInterface;

/**
 * Loads the plugin translations.
 */
final readonly class TextDomain implements SubscriberInterface
{
    public const DOMAIN = 'italy-cookie-choices';

    /**
     * @param string $languagesPath Relative to the plugins folder, as load_plugin_textdomain() expects it.
     */
    public function __construct(
        private string $languagesPath,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'init' => [
            self::CALLBACK => 'load',
            self::PRIORITY => 100,
        ];
    }

    public function load(): void
    {
        \load_plugin_textdomain(self::DOMAIN, false, $this->languagesPath);
    }
}
