<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\UI;

use ItalyCookieChoices\Migration\Application\Migrate;
use ItalyCookieChoices\RestHandler;
use ItalyCookieChoices\RestRoutes;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Event\SubscriberInterface;

/**
 * GET /wp-json/italy-cookie-choices/v1/migration/<source> previews the import of another plugin,
 * POST imports it, for the administrators.
 */
final readonly class MigrationEndpoint implements SubscriberInterface
{
    public const ROUTE = '/migration/(?P<source>[a-z0-9_-]+)';
    /** The error of a plugin without settings to import. */
    public const NOT_FOUND = 'italy_cookie_choices_unknown_migration';

    public function __construct(
        private Migrate $migrate,
        private ImportSummary $summary,
        private DismissedSources $dismissed,
        private RestRoutes $routes,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield Hook::REST_API_INIT => 'register';
    }

    public function register(): void
    {
        $this->routes->forAdministrators(
            self::ROUTE,
            new RestHandler(\WP_REST_Server::READABLE, $this->preview(...)),
            new RestHandler(\WP_REST_Server::CREATABLE, $this->import(...))
        );
    }

    public function preview(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        try {
            $imported = $this->migrate->preview($this->source($request));
        } catch (\InvalidArgumentException $exception) {
            return $this->notFound($exception);
        }

        return new \WP_REST_Response($this->summary->toArray($imported));
    }

    public function import(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $source = $this->source($request);
        try {
            $imported = $this->migrate->import($source);
        } catch (\InvalidArgumentException $exception) {
            return $this->notFound($exception);
        }

        $this->dismissed->add($source);

        return new \WP_REST_Response([
            ...$this->summary->toArray($imported),
            'settings' => \get_option(Schema::OPTION),
        ]);
    }

    private function source(\WP_REST_Request $request): string
    {
        $source = $request->get_param('source');

        return \is_string($source) ? $source : '';
    }

    private function notFound(\InvalidArgumentException $exception): \WP_Error
    {
        return new \WP_Error(self::NOT_FOUND, $exception->getMessage(), ['status' => 404]);
    }
}
