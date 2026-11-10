<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Infrastructure;

use ItalyCookieChoices\Consent\Application\ConsentCookieDomain;
use ItalyCookieChoices\Consent\Domain\ConsentStorage;

/**
 * Sends back the consent cookie of the request with a new expiry, as a cookie set by the server.
 *
 * Safari keeps the cookies written by JavaScript for 7 days at most, not the ones set by the server.
 * The value is the one the browser sent, never a value of the request body, and it is encoded
 * as encodeURIComponent() in assets/src/consent, so the script reads it as before.
 */
final readonly class CookieConsentStorage implements ConsentStorage
{
    /**
     * @var \Closure(string, string, array<string, mixed>): bool
     */
    private \Closure $sendCookie;

    /**
     * @param array<array-key, mixed> $cookies The request cookies, slashed by WordPress.
     * @param (\Closure(string, string, array<string, mixed>): bool)|null $sendCookie setrawcookie() by default.
     */
    public function __construct(
        private array $cookies,
        private ConsentCookieDomain $domain,
        ?\Closure $sendCookie = null,
    ) {
        $this->sendCookie = $sendCookie ?? $this->setRawCookie(...);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function setRawCookie(string $name, string $value, array $options): bool
    {
        /** @var array{expires: int, path: string, domain?: string, secure: bool, httponly: bool, samesite: 'Lax'} $options */
        return \setrawcookie($name, $value, $options);
    }

    public function renew(\DateTimeImmutable $expiresAt): void
    {
        $value = $this->cookies[CookieConsentRepository::COOKIE_NAME] ?? null;
        if (!\is_string($value)) {
            return;
        }

        $domain = $this->domain->forThisSite();
        ($this->sendCookie)(
            CookieConsentRepository::COOKIE_NAME,
            \rawurlencode(\wp_unslash($value)),
            [
                'expires' => $expiresAt->getTimestamp(),
                'path' => '/',
                ...($domain !== '' ? ['domain' => $domain] : []),
                'secure' => \is_ssl(),
                'httponly' => false,
                'samesite' => 'Lax',
            ]
        );
    }
}
