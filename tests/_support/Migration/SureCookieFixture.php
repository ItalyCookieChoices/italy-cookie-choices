<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Migration;

/**
 * The settings, the services and the scanned cookies of SureCookie, written for the tests from its structure.
 */
final class SureCookieFixture
{
    /**
     * @param array<string, mixed> $settings
     */
    public static function settings(array $settings): void
    {
        \update_option('surecookie_settings', $settings);
    }

    /**
     * The services the site owner added, by their slug in the catalog of SureCookie.
     *
     * @param array<string, list<string>> $services The ids of the custom cookies of each service.
     */
    public static function services(array $services): void
    {
        $installed = [];
        foreach ($services as $slug => $cookies) {
            $installed[$slug] = [
                'slug' => $slug,
                'label' => \ucwords(\str_replace('-', ' ', $slug)),
                'category' => 'marketing',
                'added_at' => 1700000000,
                'version' => '3',
                'cookie_ids' => $cookies,
            ];
        }

        \update_option('surecookie_installed_services', ['installed' => $installed, 'suppressed' => ['hotjar']]);
    }

    /**
     * @param array<string, list<array<string, string>>> $cookies The rows of the scans, by category id.
     */
    public static function scannedCookies(array $cookies): void
    {
        \update_option('surecookie_scanned_cookies', $cookies);
    }

    /**
     * @return array<string, string>
     */
    public static function cookie(string $id, string $name, string $category, string $days, string $description): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'category' => $category,
            'duration' => $days,
            'provider' => '',
            'purpose' => '',
            'domain' => '',
            'type' => 'custom',
        ];
    }
}
