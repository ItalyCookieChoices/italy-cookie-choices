<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Infrastructure;

use ItalyCookieChoices\Scan\Application\PageFetcher;

/**
 * Reads a page of the site with a loopback request from the server, like the Site Health of WordPress,
 * without cookies: the page is the one of a visitor that has not chosen yet.
 *
 * The scan parameter skips the page caches that read the query string.
 */
final readonly class LoopbackFetcher implements PageFetcher
{
    private const TIMEOUT = 15;
    private const REDIRECTIONS = 3;
    private const SCAN_PARAMETER = 'icc-scan';
    private const OK = 200;

    public function fetch(string $url): string
    {
        $response = \wp_remote_get(\add_query_arg(self::SCAN_PARAMETER, (string) \time(), $url), [
            'timeout' => self::TIMEOUT,
            'redirection' => self::REDIRECTIONS,
            /** This filter is documented in wp-includes/class-wp-http-streams.php */
            'sslverify' => (bool) \apply_filters('https_local_ssl_verify', false),
            'headers' => ['Cache-Control' => 'no-cache'],
        ]);

        if ($response instanceof \WP_Error) {
            throw new \RuntimeException(\sprintf(
                /* translators: %s: the error of the request. */
                \__('The site did not answer to the scan: %s', 'italy-cookie-choices'),
                $response->get_error_message()
            ));
        }

        $status = (int) \wp_remote_retrieve_response_code($response);
        if ($status !== self::OK) {
            throw new \RuntimeException(\sprintf(
                /* translators: %d: the HTTP status code. */
                \__('The page answered with the status %d.', 'italy-cookie-choices'),
                $status
            ));
        }

        return \wp_remote_retrieve_body($response);
    }
}
