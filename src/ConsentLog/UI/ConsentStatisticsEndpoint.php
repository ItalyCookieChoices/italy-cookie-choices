<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI;

use ItalyCookieChoices\RestHandler;
use ItalyCookieChoices\RestRoutes;
use ItalyCookieChoices\WordPress\Capability;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Event\SubscriberInterface;

/**
 * GET /wp-json/italy-cookie-choices/v1/statistics, read by the Consent log tab of the settings page.
 *
 * Only for the administrators, from the records already stored: nothing new is collected.
 */
final readonly class ConsentStatisticsEndpoint implements SubscriberInterface
{
    public const ROUTE = '/statistics';


    public function __construct(
        private StatisticsReport $report,
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
            \WP_REST_Server::READABLE,
            fn(): \WP_REST_Response => new \WP_REST_Response($this->report->toArray())
        ));
    }
}
