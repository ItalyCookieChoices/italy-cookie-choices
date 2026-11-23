<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

/**
 * The one-time tokens that open a page in scan mode, for the scan of the cookies in the browser.
 *
 * The page is loaded in a frame as an anonymous visitor, so a nonce of the logged-in user cannot work.
 * Only the hash of the token is stored, for a few minutes.
 */
final readonly class ScanTokens
{
    public const LIFETIME = 5 * \MINUTE_IN_SECONDS;

    private const TRANSIENT = 'icc_scan_token_';
    private const LENGTH = 32;

    public function issue(): string
    {
        $token = \wp_generate_password(self::LENGTH, false);
        \set_transient($this->transient($token), 1, self::LIFETIME);

        return $token;
    }

    /**
     * Whether the token is valid, it is not valid anymore after this call.
     */
    public function consume(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $transient = $this->transient($token);
        if (\get_transient($transient) === false) {
            return false;
        }

        \delete_transient($transient);

        return true;
    }

    private function transient(string $token): string
    {
        return self::TRANSIENT . \hash('sha256', $token);
    }
}
