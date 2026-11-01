<?php

declare(strict_types=1);

namespace ItalyCookieChoices\I18n;

use ItalyStrap\Event\SubscriberInterface;

/**
 * Loads the plugin translations, the bundled ones win over the language packs.
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

    /**
     * The bundled translation, that wins being loaded first, then the language pack of WordPress.org
     * for the strings it misses: an older language pack, like the 2.x one, would hide the new strings.
     */
    public function load(): void
    {
        $bundled = \sprintf(
            '%s/%s/%s-%s.mo',
            \WP_PLUGIN_DIR,
            $this->languagesPath,
            self::DOMAIN,
            \determine_locale()
        );
        if (\is_readable($bundled)) {
            \load_textdomain(self::DOMAIN, $bundled);
        }

        \load_plugin_textdomain(self::DOMAIN, false, $this->languagesPath);
    }
}
