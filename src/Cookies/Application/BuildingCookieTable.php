<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cookies\Application;

use ItalyCookieChoices\Cookies\Domain\Cookie;

/**
 * Dispatched before the cookie table is printed, listeners add the cookies of a theme or a plugin:
 *
 * add_action( BuildingCookieTable::class, static function ( BuildingCookieTable $event ): void {
 *     $event->addCookie( new Cookie( 'theme_dark', 'My theme', 'preferences', 'Remembers the dark mode.', '1 year' ) );
 * } );
 */
final class BuildingCookieTable
{
    /**
     * @param list<Cookie> $cookies
     */
    public function __construct(
        private array $cookies,
    ) {
    }

    public function addCookie(Cookie $cookie): void
    {
        $this->cookies[] = $cookie;
    }

    public function removeCookie(string $name): void
    {
        $this->cookies = \array_values(\array_filter(
            $this->cookies,
            static fn(Cookie $cookie): bool => $cookie->name() !== $name
        ));
    }

    /**
     * @return list<Cookie>
     */
    public function cookies(): array
    {
        return $this->cookies;
    }
}
