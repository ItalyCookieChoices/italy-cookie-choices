<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI;

use ItalyCookieChoices\RestHandler;
use ItalyCookieChoices\RestRoutes;
use ItalyCookieChoices\Scan\Application\ScanAssistant;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Event\SubscriberInterface;

/**
 * POST /wp-json/italy-cookie-choices/v1/scan/suggestions: the suggestions of the scan assistant
 * for the unknown third parties and cookies, only when the site owner asks for them.
 */
final readonly class AssistantEndpoint implements SubscriberInterface
{
    public const ROUTE = ScanEndpoint::ROUTE . '/suggestions';

    private const HOSTS = 'hosts';
    private const COOKIES = 'cookies';

    public function __construct(
        private ScanAssistant $assistant,
        private RestRoutes $routes,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield Hook::REST_API_INIT => 'register';
    }

    public function register(): void
    {
        $names = [
            'type' => 'array',
            'items' => ['type' => 'string'],
            'maxItems' => ScanAssistant::LIMIT,
            'default' => [],
        ];

        $this->routes->forAdministrators(self::ROUTE, new RestHandler(
            \WP_REST_Server::CREATABLE,
            $this->suggestions(...),
            [self::HOSTS => $names, self::COOKIES => $names]
        ));
    }

    public function suggestions(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        // Without an AI provider the generator fails with the reason.
        try {
            $suggestions = $this->assistant->suggest(
                $this->strings($request->get_param(self::HOSTS)),
                $this->strings($request->get_param(self::COOKIES))
            );
        } catch (\RuntimeException $exception) {
            return new \WP_Error('italy_cookie_choices_assistant_failed', $exception->getMessage(), ['status' => 502]);
        }

        return new \WP_REST_Response($suggestions);
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $values): array
    {
        return \array_values(\array_filter(\is_array($values) ? $values : [], \is_string(...)));
    }
}
