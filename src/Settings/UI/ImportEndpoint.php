<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyCookieChoices\Settings\Migration;
use ItalyCookieChoices\Settings\Schema;
use ItalyStrap\Event\SubscriberInterface;

/**
 * POST /wp-json/italy-cookie-choices/v1/settings/import, it saves the settings of an exported file.
 *
 * The file of the settings page is {"version": 5, "settings": {...}}, the one of the 2.x versions is [{...}].
 * The settings are migrated from their version, then saved through the sanitizer.
 */
final readonly class ImportEndpoint implements SubscriberInterface
{
    public const NAMESPACE = 'italy-cookie-choices/v1';
    public const ROUTE = '/settings/import';

    /**
     * The 2.x files have no version, their option has the shape of the version 1.
     */
    private const LEGACY_VERSION = 1;

    public function __construct(
        private Migration $migration,
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
        $file = \is_array($file) ? $file : [];
        $version = $this->version($file);
        $settings = $this->settings($file);
        if ($settings === null || $version > Schema::VERSION) {
            return new \WP_Error(
                'italy_cookie_choices_invalid_import',
                \__('The file has no settings of Italy Cookie Choices.', 'italy-cookie-choices'),
                ['status' => 400]
            );
        }

        \update_option(Schema::OPTION, $this->migration->upgrade($settings, $version));

        return new \WP_REST_Response(\get_option(Schema::OPTION));
    }

    /**
     * @param array<array-key, mixed> $file
     */
    private function version(array $file): int
    {
        $version = $file['version'] ?? self::LEGACY_VERSION;

        return \is_int($version) ? $version : self::LEGACY_VERSION;
    }

    /**
     * @param array<array-key, mixed> $file
     * @return array<array-key, mixed>|null
     */
    private function settings(array $file): ?array
    {
        $settings = \array_is_list($file) ? $file[0] ?? null : $file['settings'] ?? null;

        return \is_array($settings) && $settings !== [] && !\array_is_list($settings) ? $settings : null;
    }
}
