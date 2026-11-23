<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI;

use ItalyCookieChoices\Scan\Application\ScanTokens;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Whether the current page is loaded by the scan of the cookies in the browser, with a valid one-time token.
 *
 * In scan mode the page accepts every category in memory: no consent cookie, no consent log, no banner.
 * It starts after the redirects of WordPress, so a redirect does not spend the token, and the page is never cached.
 * Shared, every class of the request reads the same state.
 */
final class ScanRequest implements SubscriberInterface
{
    public const QUERY_ARG = 'icc_scan';

    private bool $isScan = false;

    /**
     * @param array<array-key, mixed> $query The query of the request.
     */
    public function __construct(
        private readonly ScanTokens $tokens,
        private readonly array $query,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        // After redirect_canonical() and the other redirects, before PageBuffer.
        yield Hook::TEMPLATE_REDIRECT => [
            self::CALLBACK => 'start',
            self::PRIORITY => \PHP_INT_MAX - 1,
        ];
    }

    public function start(): void
    {
        $token = $this->query[self::QUERY_ARG] ?? null;
        if (!\is_string($token) || !$this->tokens->consume($token)) {
            return;
        }

        $this->isScan = true;
        \nocache_headers();
        if (!\defined('DONOTCACHEPAGE')) {
            \define('DONOTCACHEPAGE', true);
        }
    }

    public function isScan(): bool
    {
        return $this->isScan;
    }
}
