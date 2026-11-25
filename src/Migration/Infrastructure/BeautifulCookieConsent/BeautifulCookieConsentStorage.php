<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\BeautifulCookieConsent;

/**
 * Reads the settings Beautiful Cookie Consent keeps in the database, also when it is deactivated:
 * the configuration of its banner as JSON, and the other settings in their own options.
 */
final readonly class BeautifulCookieConsentStorage
{
    private const PREFIX = 'nsc_bar_';
    private const BANNER = 'bannersettings_json';
    private const VERSION = 'db_version';

    public function hasSettings(): bool
    {
        return \get_option(self::PREFIX . self::VERSION) !== false || $this->banner() !== [];
    }

    /**
     * The configuration of the banner.
     *
     * @return array<array-key, mixed>
     */
    public function banner(): array
    {
        $json = \get_option(self::PREFIX . self::BANNER);
        $banner = \is_string($json) ? \json_decode($json, true) : null;

        return \is_array($banner) ? $banner : [];
    }

    /**
     * A setting in its own option, by its name without the prefix.
     */
    public function option(string $name): mixed
    {
        return \get_option(self::PREFIX . $name, null);
    }
}
