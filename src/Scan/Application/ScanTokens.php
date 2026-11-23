<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

use Psr\SimpleCache\CacheInterface;

/**
 * The one-time tokens that open a page in scan mode, for the scan of the cookies in the browser.
 *
 * The page is loaded in a frame as an anonymous visitor, so a nonce of the logged-in user cannot work.
 * Only the hash of the token is stored, for a few minutes.
 */
final readonly class ScanTokens
{
    public const LIFETIME = 5 * \MINUTE_IN_SECONDS;

    private const KEY = 'icc_scan_token_';
    private const LENGTH = 32;
    private const VALID = 1;

    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function issue(): string
    {
        $token = \wp_generate_password(self::LENGTH, false);
        $this->cache->set($this->key($token), self::VALID, self::LIFETIME);

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

        $key = $this->key($token);
        if (!$this->cache->has($key)) {
            return false;
        }

        $this->cache->delete($key);

        return true;
    }

    private function key(string $token): string
    {
        return self::KEY . \hash('sha256', $token);
    }
}
