<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cookies\Domain;

/**
 * The cookies of the site, by consent category, as the cookie policy lists them.
 *
 * A cookie is listed once: the first one with a name wins, the plugin adds its own cookies first.
 */
final readonly class CookieTable
{
    /**
     * @var array<string, Cookie>
     */
    private array $cookies;

    /**
     * @param list<Cookie> $cookies
     */
    public function __construct(array $cookies)
    {
        $unique = [];
        foreach ($cookies as $cookie) {
            $unique[$cookie->name()] ??= $cookie;
        }

        $this->cookies = $unique;
    }

    /**
     * @return list<Cookie>
     */
    public function inCategory(string $category): array
    {
        return \array_values(\array_filter(
            $this->cookies,
            static fn(Cookie $cookie): bool => $cookie->category() === $category
        ));
    }

    public function isEmpty(): bool
    {
        return $this->cookies === [];
    }
}
