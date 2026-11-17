<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI;

use ItalyCookieChoices\Scan\Application\ScanTargets;
use ItalyCookieChoices\Scan\Application\SiteScanner;
use ItalyCookieChoices\Scan\Domain\Selection;
use ItalyCookieChoices\Settings\UI\ImportEndpoint;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The scan of the settings page, for the administrators:
 *
 * * POST /wp-json/italy-cookie-choices/v1/scan/targets lists the pages of a selection;
 * * POST /wp-json/italy-cookie-choices/v1/scan/pages scans a batch of them, the page sends the batches in turn.
 */
final readonly class ScanEndpoint implements SubscriberInterface
{
    public const ROUTE = '/scan';
    public const BATCH = 10;

    public function __construct(
        private ScanTargets $targets,
        private SiteScanner $scanner,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'rest_api_init' => 'register';
    }

    public function register(): void
    {
        $permission = static fn(): bool => \current_user_can(SettingsPage::CAPABILITY);
        $ids = ['type' => 'array', 'items' => ['type' => 'integer', 'minimum' => 0]];

        \register_rest_route(ImportEndpoint::NAMESPACE, self::ROUTE . '/targets', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => $this->listTargets(...),
            'permission_callback' => $permission,
            'args' => [
                'home' => ['type' => 'boolean', 'default' => false],
                'postTypes' => ['type' => 'array', 'items' => ['type' => 'string'], 'default' => []],
                'categories' => [...$ids, 'default' => []],
                'posts' => [...$ids, 'default' => []],
            ],
        ]);
        \register_rest_route(ImportEndpoint::NAMESPACE, self::ROUTE . '/pages', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => $this->scanPages(...),
            'permission_callback' => $permission,
            'args' => [
                'ids' => [...$ids, 'required' => true, 'maxItems' => self::BATCH],
            ],
        ]);
    }

    public function listTargets(\WP_REST_Request $request): \WP_REST_Response
    {
        $targets = $this->targets->of(new Selection(
            (bool) $request->get_param('home'),
            $this->strings($request->get_param('postTypes')),
            $this->integers($request->get_param('categories')),
            $this->integers($request->get_param('posts')),
        ));

        return new \WP_REST_Response(['targets' => $targets->targets(), 'truncated' => $targets->isTruncated()]);
    }

    public function scanPages(\WP_REST_Request $request): \WP_REST_Response
    {
        return new \WP_REST_Response(['pages' => $this->scanner->scan($this->integers($request->get_param('ids')))]);
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $values): array
    {
        return \array_values(\array_filter(\is_array($values) ? $values : [], \is_string(...)));
    }

    /**
     * @return list<int>
     */
    private function integers(mixed $values): array
    {
        return \array_values(\array_filter(\is_array($values) ? $values : [], \is_int(...)));
    }
}
