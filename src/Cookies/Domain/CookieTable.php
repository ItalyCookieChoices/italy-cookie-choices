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
    private array $unique;

    /**
     * @param list<Cookie> $cookies
     */
    public function __construct(private array $cookies)
    {
        $unique = [];
        foreach ($cookies as $cookie) {
            $unique[$cookie->name()] ??= $cookie;
        }

        $this->unique = $unique;
    }

    /**
     * @return list<Cookie>
     */
    public function inCategory(string $category): array
    {
        return \array_values(\array_filter(
            $this->unique,
            static fn(Cookie $cookie): bool => $cookie->category() === $category
        ));
    }

    /**
     * The cookies of each vendor, a name two vendors share is listed for both, the cookies of the site are left out.
     *
     * @return array<string, non-empty-list<Cookie>>
     */
    public function byVendor(): array
    {
        $vendors = [];
        foreach ($this->cookies as $cookie) {
            if ($cookie->vendor() === '') {
                continue;
            }

            $vendors[$cookie->vendor()][] = $cookie;
        }

        return $vendors;
    }

    public function isEmpty(): bool
    {
        return $this->unique === [];
    }
}
