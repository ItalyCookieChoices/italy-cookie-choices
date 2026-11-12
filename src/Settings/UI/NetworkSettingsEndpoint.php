<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyStrap\Event\SubscriberInterface;

/**
 * GET and POST /wp-json/italy-cookie-choices/v1/network, the settings of the network page, for the super admins.
 */
final readonly class NetworkSettingsEndpoint implements SubscriberInterface
{
    public const ROUTE = '/network';

    public function __construct(
        private NetworkSettings $network,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'rest_api_init' => 'register';
    }

    public function register(): void
    {
        if (!\is_multisite()) {
            return;
        }

        $permission = static fn(): bool => \current_user_can(NetworkSettingsPage::CAPABILITY);

        \register_rest_route(ImportEndpoint::NAMESPACE, self::ROUTE, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => $this->read(...),
                'permission_callback' => $permission,
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => $this->save(...),
                'permission_callback' => $permission,
                'args' => [
                    'shared' => ['type' => 'boolean', 'required' => true],
                ],
            ],
        ]);
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
