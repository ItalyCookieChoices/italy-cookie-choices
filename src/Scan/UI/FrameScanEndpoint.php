<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI;

use ItalyCookieChoices\RestHandler;
use ItalyCookieChoices\RestRoutes;
use ItalyCookieChoices\Scan\Application\ScanTokens;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Event\SubscriberInterface;

/**
 * POST /wp-json/italy-cookie-choices/v1/scan/frame gives the address of a page of the site in scan mode,
 * with a one-time token: the settings page loads it in a frame and reads the cookies the page sets.
 */
final readonly class FrameScanEndpoint implements SubscriberInterface
{
    public const ROUTE = ScanEndpoint::ROUTE . '/frame';

    public function __construct(
        private ScanTokens $tokens,
        private RestRoutes $routes,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield Hook::REST_API_INIT => 'register';
    }

    public function register(): void
    {
        $this->routes->forAdministrators(self::ROUTE, new RestHandler(
            \WP_REST_Server::CREATABLE,
            $this->frameUrl(...),
            ['url' => ['type' => 'string', 'format' => 'uri', 'required' => true]]
        ));
    }

    public function frameUrl(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $url = $request->get_param('url');
        if (!\is_string($url) || !$this->isOfThisSite($url)) {
            return new \WP_Error(
                'icc_scan_foreign_url',
                \__('Only the pages of this site can be scanned.', 'italy-cookie-choices'),
                ['status' => 400]
            );
        }

        return new \WP_REST_Response(['url' => \add_query_arg(ScanRequest::QUERY_ARG, $this->tokens->issue(), $url)]);
    }

    private function isOfThisSite(string $url): bool
    {
        $host = \wp_parse_url($url, \PHP_URL_HOST);
        $siteHost = (string) \wp_parse_url(\home_url(), \PHP_URL_HOST);

        return \is_string($host) && \strtolower($host) === \strtolower($siteHost);
    }
}
