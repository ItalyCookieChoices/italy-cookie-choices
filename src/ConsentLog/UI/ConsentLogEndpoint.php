<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI;

use ItalyCookieChoices\RestApi;
use ItalyCookieChoices\ConsentLog\Application\LogSettings;
use ItalyCookieChoices\ConsentLog\Application\RecordConsent;
use ItalyStrap\Event\SubscriberInterface;

/**
 * POST /wp-json/italy-cookie-choices/v1/consents, where the consent script sends each choice.
 *
 * It is public like the consent itself: the pages can be cached, so there is no nonce,
 * and the body is validated against the current policy.
 */
final readonly class ConsentLogEndpoint implements SubscriberInterface
{
    public const ROUTE = '/consents';

    public function __construct(
        private RecordConsent $record,
        private LogSettings $settings,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'rest_api_init' => 'register';
    }

    public function register(): void
    {
        if (!$this->settings->isEnabled()) {
            return;
        }

        \register_rest_route(RestApi::NAMESPACE, self::ROUTE, [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => $this->create(...),
            'permission_callback' => '__return_true',
            'args' => [
                'id' => ['type' => 'string', 'format' => 'uuid', 'required' => true],
                'categories' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'maxLength' => 64],
                    'maxItems' => 20,
                    'uniqueItems' => true,
                    'required' => true,
                ],
                'version' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 64, 'required' => true],
                'refusedVendors' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'pattern' => '^[a-z0-9_-]{1,64}$'],
                    'maxItems' => 100,
                    'uniqueItems' => true,
                    'default' => [],
                ],
            ],
        ]);
    }

    public function create(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $categories = $request->get_param('categories');
        $refusedVendors = $request->get_param('refusedVendors');

        try {
            ($this->record)(
                $this->text($request->get_param('id')),
                \is_array($categories) ? \array_values(\array_map($this->text(...), $categories)) : [],
                $this->text($request->get_param('version')),
                new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
                \is_array($refusedVendors) ? \array_values(\array_map($this->text(...), $refusedVendors)) : []
            );
        } catch (\InvalidArgumentException $exception) {
            return new \WP_Error('italy_cookie_choices_invalid_consent', $exception->getMessage(), ['status' => 400]);
        }

        return new \WP_REST_Response(null, 201);
    }

    /**
     * The REST schema has already checked the types.
     */
    private function text(mixed $value): string
    {
        return \is_string($value) ? $value : '';
    }
}
