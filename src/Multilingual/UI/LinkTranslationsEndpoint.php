<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\UI;

use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Multilingual\Application\LinkTranslations;
use ItalyCookieChoices\RestHandler;
use ItalyCookieChoices\RestRoutes;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Event\SubscriberInterface;

/**
 * POST /wp-json/italy-cookie-choices/v1/link-translations uses the proposed translation of the page of a link
 * of the banner, the policy or the legal notice, as its link in a language of WPML or Polylang,
 * for the administrators.
 */
final readonly class LinkTranslationsEndpoint implements SubscriberInterface
{
    public const ROUTE = '/link-translations';

    public function __construct(
        private LinkTranslations $translations,
        private RestRoutes $routes,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield Hook::REST_API_INIT => 'register';
    }

    public function register(): void
    {
        $this->routes->forAdministrators(self::ROUTE, new RestHandler(\WP_REST_Server::CREATABLE, $this->use(...), [
            'language' => ['type' => 'string', 'required' => true],
            'link' => [
                'type' => 'string',
                'enum' => \array_column(BannerLink::cases(), 'value'),
                'default' => BannerLink::Policy->value,
            ],
        ]));
    }

    public function use(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $language = $request->get_param('language');
        $link = $request->get_param('link');
        $link = BannerLink::tryFrom(\is_string($link) ? $link : '') ?? BannerLink::Policy;

        try {
            $rows = $this->translations->use($link, \is_string($language) ? $language : '');
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            return new \WP_Error(
                'italy_cookie_choices_invalid_link_translation',
                $exception->getMessage(),
                ['status' => 400]
            );
        }

        return new \WP_REST_Response(['rows' => $rows]);
    }
}
