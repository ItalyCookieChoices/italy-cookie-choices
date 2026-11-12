<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyCookieChoices\Settings\SettingsCopy;
use ItalyStrap\Event\SubscriberInterface;

/**
 * POST /wp-json/italy-cookie-choices/v1/network/copy, it copies the settings of a site to other sites of the network.
 */
final readonly class CopySettingsEndpoint implements SubscriberInterface
{
    public const ROUTE = '/network/copy';

    public function __construct(
        private SettingsCopy $copy,
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

        \register_rest_route(ImportEndpoint::NAMESPACE, self::ROUTE, [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => $this->copy(...),
            'permission_callback' => static fn(): bool => \current_user_can(NetworkSettingsPage::CAPABILITY),
            'args' => [
                'source' => ['type' => 'integer', 'required' => true],
                'targets' => ['type' => 'array', 'items' => ['type' => 'integer'], 'required' => true],
                'texts' => ['type' => 'boolean', 'default' => false],
            ],
        ]);
    }

    public function copy(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $targets = $request->get_param('targets');

        try {
            $copied = $this->copy->copy(
                $this->siteId($request->get_param('source')),
                \array_values(\array_map($this->siteId(...), \is_array($targets) ? $targets : [])),
                (bool) $request->get_param('texts')
            );
        } catch (\InvalidArgumentException $exception) {
            return new \WP_Error('italy_cookie_choices_invalid_copy', $exception->getMessage(), ['status' => 400]);
        }

        return new \WP_REST_Response(['copied' => $copied]);
    }

    /**
     * The id of a site, validated as an integer by the arguments of the route.
     */
    private function siteId(mixed $value): int
    {
        return \is_numeric($value) ? (int) $value : 0;
    }
}
