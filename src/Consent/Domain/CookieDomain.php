<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Domain;

/**
 * The domain of the consent cookie, like example.com to share the consent with www.example.com and shop.example.com.
 *
 * The browsers accept it only for the host of the page or one of its parent domains.
 */
final readonly class CookieDomain
{
    private const PATTERN = '/^[a-z0-9-]+(\.[a-z0-9-]+)+$/';

    private string $domain;

    public function __construct(string $domain)
    {
        $this->domain = \ltrim(\strtolower(\trim($domain)), '.');
    }

    /**
     * The domain when it is the host or one of its parent domains, empty otherwise.
     */
    public function forHost(string $host): string
    {
        $host = \strtolower($host);
        if (\preg_match(self::PATTERN, $this->domain) !== 1) {
            return '';
        }

        if ($host === $this->domain || \str_ends_with($host, '.' . $this->domain)) {
            return $this->domain;
        }

        return '';
    }
}
