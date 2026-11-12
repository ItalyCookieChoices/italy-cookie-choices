<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\UI;

use ItalyCookieChoices\Multilingual\Application\ConnectedSites;
use ItalyCookieChoices\Multilingual\Application\PolicySuggestion;
use ItalyCookieChoices\Multilingual\Application\SiteTexts;
use ItalyCookieChoices\Settings\UI\ImportEndpoint;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The texts of this site and of the sites connected to it, from the settings page:
 *
 * * GET and POST /wp-json/italy-cookie-choices/v1/sites/{id}/texts read and save them;
 * * POST /wp-json/italy-cookie-choices/v1/sites/{id}/texts/draft fills the empty ones from a connected site,
 *   without saving them.
 *
 * Only for the users that are administrators of the site.
 */
final readonly class SiteTextsEndpoint implements SubscriberInterface
{
    public const ROUTE = '/sites/(?P<site>\d+)/texts';

    public function __construct(
        private SiteTexts $texts,
        private PolicySuggestion $policy,
        private ConnectedSites $sites,
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

        $site = ['site' => ['type' => 'integer', 'required' => true]];
        \register_rest_route(ImportEndpoint::NAMESPACE, self::ROUTE, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => $this->read(...),
                'permission_callback' => $this->canEdit(...),
                'args' => $site,
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => $this->save(...),
                'permission_callback' => $this->canEdit(...),
                'args' => [...$site, 'texts' => ['type' => 'object', 'required' => true]],
            ],
        ]);
        \register_rest_route(ImportEndpoint::NAMESPACE, self::ROUTE . '/draft', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => $this->draft(...),
            'permission_callback' => $this->canEdit(...),
            'args' => [
                ...$site,
                'source' => ['type' => 'integer', 'required' => true],
                'translate' => ['type' => 'boolean', 'default' => false],
            ],
        ]);
    }

    /**
     * This site or a site connected to it, for an administrator of that site.
     */
    public function canEdit(\WP_REST_Request $request): bool
    {
        $siteId = $this->siteOf($request);
        $isReachable = $siteId === \get_current_blog_id()
            || \in_array($siteId, $this->sites->of(\get_current_blog_id()), true);

        return $isReachable && \current_user_can_for_site($siteId, SettingsPage::CAPABILITY);
    }

    public function read(\WP_REST_Request $request): \WP_REST_Response
    {
        return $this->response($this->siteOf($request), $this->texts->of($this->siteOf($request)));
    }

    public function save(\WP_REST_Request $request): \WP_REST_Response
    {
        $texts = $request->get_param('texts');
        $siteId = $this->siteOf($request);

        return $this->response($siteId, $this->texts->save($siteId, \is_array($texts) ? $texts : []));
    }

    public function draft(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $siteId = $this->siteOf($request);
        $source = $request->get_param('source');

        try {
            $draft = $this->texts->draft(
                $siteId,
                \is_numeric($source) ? (int) $source : 0,
                (bool) $request->get_param('translate')
            );
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            return new \WP_Error('italy_cookie_choices_invalid_draft', $exception->getMessage(), ['status' => 400]);
        }

        return $this->response($siteId, $draft);
    }

    /**
     * @param array<string, mixed> $texts
     */
    private function response(int $siteId, array $texts): \WP_REST_Response
    {
        return new \WP_REST_Response([
            'texts' => $texts,
            'policySuggestion' => $this->policy->for($siteId),
        ]);
    }

    private function siteOf(\WP_REST_Request $request): int
    {
        $siteId = $request->get_param('site');

        return \is_numeric($siteId) ? (int) $siteId : 0;
    }
}
