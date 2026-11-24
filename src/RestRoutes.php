<?php

declare(strict_types=1);

namespace ItalyCookieChoices;

use ItalyCookieChoices\WordPress\Capability;

/**
 * Registers the REST routes of the plugin in its namespace, called on rest_api_init.
 */
final readonly class RestRoutes
{
    /**
     * A route for the users who manage the settings of the plugin.
     *
     * @param non-falsy-string $route
     */
    public function forAdministrators(string $route, RestHandler ...$handlers): void
    {
        $this->forUsersWho(static fn(): bool => \current_user_can(Capability::MANAGE_OPTIONS), $route, ...$handlers);
    }

    /**
     * A route for every visitor, like the consent given in the banner.
     *
     * @param non-falsy-string $route
     */
    public function forEveryone(string $route, RestHandler ...$handlers): void
    {
        $this->forUsersWho('__return_true', $route, ...$handlers);
    }

    /**
     * @param callable(\WP_REST_Request): (bool|\WP_Error) $permission Who can use the route.
     * @param non-falsy-string $route
     */
    public function forUsersWho(callable $permission, string $route, RestHandler ...$handlers): void
    {
        \register_rest_route(RestApi::NAMESPACE, $route, \array_map(
            static fn(RestHandler $handler): array => $handler->toArray($permission),
            $handlers
        ));
    }
}
