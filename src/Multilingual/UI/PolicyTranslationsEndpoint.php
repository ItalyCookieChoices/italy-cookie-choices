<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\UI;

use ItalyCookieChoices\Multilingual\Application\PolicyTranslations;
use ItalyCookieChoices\Settings\UI\ImportEndpoint;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyStrap\Event\SubscriberInterface;

/**
 * POST /wp-json/italy-cookie-choices/v1/policy-translations uses the proposed translation of the policy page
 * as the policy link of a language of WPML or Polylang, for the administrators.
 */
final readonly class PolicyTranslationsEndpoint implements SubscriberInterface
{
    public const ROUTE = '/policy-translations';

    public function __construct(
        private PolicyTranslations $translations,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'rest_api_init' => 'register';
    }

    public function register(): void
    {
        \register_rest_route(ImportEndpoint::NAMESPACE, self::ROUTE, [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => $this->use(...),
            'permission_callback' => static fn(): bool => \current_user_can(SettingsPage::CAPABILITY),
            'args' => [
                'language' => ['type' => 'string', 'required' => true],
            ],
        ]);
    }

    public function use(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $language = $request->get_param('language');

        try {
            $rows = $this->translations->use(\is_string($language) ? $language : '');
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            return new \WP_Error(
                'italy_cookie_choices_invalid_policy_translation',
                $exception->getMessage(),
                ['status' => 400]
            );
        }

        return new \WP_REST_Response(['rows' => $rows]);
    }
}
