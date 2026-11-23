<?php

declare(strict_types=1);

namespace ItalyCookieChoices;

/**
 * A method of a REST route of the plugin, with its callback and its arguments.
 */
final readonly class RestHandler
{
    /**
     * @param string $methods The HTTP methods, like WP_REST_Server::CREATABLE.
     * @param array<string, array<string, mixed>> $args The arguments, with their JSON schema.
     */
    public function __construct(
        private string $methods,
        private \Closure $callback,
        private array $args = [],
    ) {
    }

    /**
     * The handler in the shape of register_rest_route().
     *
     * @return array<string, mixed>
     */
    public function toArray(callable $permission): array
    {
        return [
            'methods' => $this->methods,
            'callback' => $this->callback,
            'permission_callback' => $permission,
            ...($this->args !== [] ? ['args' => $this->args] : []),
        ];
    }
}
