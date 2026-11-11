<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Settings\SettingsFile;
use ItalyStrap\Event\SubscriberInterface;

/**
 * POST /wp-json/italy-cookie-choices/v1/settings/import, it saves the settings of an exported file.
 *
 * The file is read by SettingsFile, the WP-CLI command `wp cookie-choices settings import` reads the same.
 */
final readonly class ImportEndpoint implements SubscriberInterface
{
    public const NAMESPACE = 'italy-cookie-choices/v1';
    public const ROUTE = '/settings/import';

    public function __construct(
        private SettingsFile $file,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'rest_api_init' => 'register';
    }

    public function register(): void
    {
        \register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => $this->import(...),
            'permission_callback' => static fn(): bool => \current_user_can(SettingsPage::CAPABILITY),
            'args' => [
                'file' => ['type' => ['object', 'array'], 'required' => true],
            ],
        ]);
    }

    public function import(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $file = $request->get_param('file');

        try {
            $this->file->import(\is_array($file) ? $file : []);
        } catch (\InvalidArgumentException $exception) {
            return new \WP_Error('italy_cookie_choices_invalid_import', $exception->getMessage(), ['status' => 400]);
        }

        return new \WP_REST_Response(\get_option(Schema::OPTION));
    }
}
