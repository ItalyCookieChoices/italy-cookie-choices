<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Consent\Application\RenewConsent;
use ItalyCookieChoices\Settings\Options;
use ItalyStrap\Event\SubscriberInterface;

/**
 * POST /wp-json/italy-cookie-choices/v1/consent-cookie, called by the consent script after each choice.
 *
 * It sets again the consent cookie that the browser sends, so Safari keeps it for its full duration.
 * It is public, the pages can be cached so there is no nonce: it reads no body, a request from another site
 * can only send back the cookie the visitor already has.
 */
final readonly class ConsentCookieEndpoint implements SubscriberInterface
{
    public const NAMESPACE = 'italy-cookie-choices/v1';
    public const ROUTE = '/consent-cookie';
    public const PATH = '/' . self::NAMESPACE . self::ROUTE;

    public function __construct(
        private RenewConsent $renew,
        private Options $options,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'rest_api_init' => 'register';
    }

    public function register(): void
    {
        if (!$this->options->isActive()) {
            return;
        }

        \register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => $this->renewTheCookie(...),
            'permission_callback' => '__return_true',
        ]);
    }

    private function renewTheCookie(): \WP_REST_Response
    {
        ($this->renew)(new \DateTimeImmutable());

        $response = new \WP_REST_Response(null, 204);
        $response->header('Cache-Control', 'no-store');

        return $response;
    }
}
