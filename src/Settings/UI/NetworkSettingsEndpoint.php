<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyCookieChoices\RestHandler;
use ItalyCookieChoices\RestRoutes;
use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Event\SubscriberInterface;

/**
 * GET and POST /wp-json/italy-cookie-choices/v1/network, the settings of the network page, for the super admins.
 */
final readonly class NetworkSettingsEndpoint implements SubscriberInterface
{
    public const ROUTE = '/network';

    public function __construct(
        private NetworkSettings $network,
        private RestRoutes $routes,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield Hook::REST_API_INIT => 'register';
    }

    public function register(): void
    {
        if (!\is_multisite()) {
            return;
        }

        $this->routes->forUsersWho(
            static fn(): bool => \current_user_can(NetworkSettingsPage::CAPABILITY),
            self::ROUTE,
            new RestHandler(\WP_REST_Server::READABLE, $this->read(...)),
            new RestHandler(\WP_REST_Server::EDITABLE, $this->save(...), [
                'shared' => ['type' => 'boolean', 'required' => true],
            ])
        );
    }

    public function read(): \WP_REST_Response
    {
        return new \WP_REST_Response(['shared' => $this->network->sharesTheMainSite()]);
    }

    public function save(\WP_REST_Request $request): \WP_REST_Response
    {
        $this->network->share((bool) $request->get_param('shared'));

        return $this->read();
    }
}
