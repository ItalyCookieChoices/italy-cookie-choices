<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI;

use ItalyStrap\Event\SubscriberInterface;

/**
 * GET /wp-json/italy-cookie-choices/v1/statistics, read by the Consent log tab of the settings page.
 *
 * Only for the administrators, from the records already stored: nothing new is collected.
 */
final readonly class ConsentStatisticsEndpoint implements SubscriberInterface
{
    public const ROUTE = '/statistics';

    private const CAPABILITY = 'manage_options';

    public function __construct(
        private StatisticsReport $report,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'rest_api_init' => 'register';
    }

    public function register(): void
    {
        \register_rest_route(ConsentLogEndpoint::NAMESPACE, self::ROUTE, [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => fn(): \WP_REST_Response => new \WP_REST_Response($this->report->toArray()),
            'permission_callback' => static fn(): bool => \current_user_can(self::CAPABILITY),
        ]);
    }
}
