<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Application;

use ItalyCookieChoices\Consent\Domain\CookieDomain;
use ItalyCookieChoices\Settings\Options;

/**
 * The saved cookie domain, when it fits the host of the site.
 *
 * On a network that shares the settings of the main site, a site on another domain keeps the cookie of its host.
 */
final readonly class ConsentCookieDomain
{
    public function __construct(
        private Options $options,
    ) {
    }

    public function forThisSite(): string
    {
        return (new CookieDomain($this->options->text('cookie_domain')))
            ->forHost((string) \wp_parse_url(\home_url(), \PHP_URL_HOST));
    }
}
