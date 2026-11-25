<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Application;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Cookies\Application\PresetCookies;

/**
 * The common vendor a cookie another plugin found belongs to, by the names of the cookies of the vendors.
 */
final readonly class KnownCookies
{
    /** The end of a name that is a prefix, like `_ga_*`. */
    private const PREFIX = '*';

    public function __construct(
        private VendorPresets $presets,
        private PresetCookies $presetCookies,
    ) {
    }

    /**
     * The id of the common vendor of the cookie, null for none.
     */
    public function vendorOf(string $name): ?string
    {
        foreach ($this->presets->all() as $vendor) {
            foreach (\array_column($this->presetCookies->of($vendor->id()), 'name') as $pattern) {
                if ($this->matches($name, (string) $pattern)) {
                    return $vendor->id();
                }
            }
        }

        return null;
    }

    private function matches(string $name, string $pattern): bool
    {
        return \str_ends_with($pattern, self::PREFIX)
            ? \str_starts_with($name, \substr($pattern, 0, -1))
            : $pattern === $name;
    }
}
