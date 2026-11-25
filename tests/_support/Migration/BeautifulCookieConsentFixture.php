<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Migration;

/**
 * The settings of Beautiful Cookie Consent, written for the tests from its structure: the banner as JSON
 * in one option, the other settings in their own options.
 */
final class BeautifulCookieConsentFixture
{
    private const PREFIX = 'nsc_bar_';

    /**
     * @param array<string, mixed> $banner
     */
    public static function banner(array $banner): void
    {
        \update_option(self::PREFIX . 'bannersettings_json', (string) \wp_json_encode($banner));
    }

    public static function option(string $name, string $value): void
    {
        \update_option(self::PREFIX . $name, $value);
    }
}
