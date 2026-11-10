<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

/**
 * The choice of the network administrator of a multisite: every site its own settings,
 * or the settings of the main site, with the texts of each site.
 */
final readonly class NetworkSettings
{
    public const OPTION = 'italy_cookie_choices_network';

    private const SHARED = 'shared';

    public function sharesTheMainSite(): bool
    {
        $option = \get_site_option(self::OPTION, []);

        return \is_array($option) && \array_key_exists(self::SHARED, $option);
    }

    public function share(bool $shared): void
    {
        \update_site_option(self::OPTION, $shared ? [self::SHARED => '1'] : []);
    }

    /**
     * Whether the current site reads the settings of the main site, never on a single site.
     */
    public function usesTheMainSite(): bool
    {
        return \is_multisite() && !\is_main_site() && $this->sharesTheMainSite();
    }
}
