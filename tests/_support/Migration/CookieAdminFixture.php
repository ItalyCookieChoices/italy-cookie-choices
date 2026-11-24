<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Migration;

/**
 * The options and the table of the scanned cookies of CookieAdmin, written for the tests from its structure.
 */
final class CookieAdminFixture
{
    private const TABLE = 'cookieadmin_cookies';

    public static function install(): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        \update_option('cookieadmin_version', '1.2.6');
        $table = $wpdb->prefix . self::TABLE;
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
        $wpdb->query(
            "CREATE TABLE {$table} (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, cookie_name VARCHAR(100) NOT NULL,
            category VARCHAR(50) DEFAULT NULL, description VARCHAR(500) DEFAULT NULL, domain VARCHAR(255) NOT NULL,
            expires DATETIME NULL DEFAULT NULL, max_age INT(11) NULL DEFAULT NULL,
            scan_timestamp INT(11) NULL DEFAULT 0)"
        );
    }

    /**
     * @param array<string, mixed> $banner
     */
    public static function banner(array $banner): void
    {
        \update_option('cookieadmin_consent_settings', ['cookieadmin_gdpr' => $banner]);
    }

    /**
     * @param array<string, mixed> $settings
     */
    public static function settings(array $settings): void
    {
        \update_option('cookieadmin_settings', $settings);
    }

    public static function cookie(
        string $name,
        string $category,
        string $description,
        ?int $maxAge = null,
        string $expires = '',
        int $scanned = 0
    ): void {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $wpdb->insert($wpdb->prefix . self::TABLE, \array_filter([
            'cookie_name' => $name,
            'category' => $category,
            'description' => $description,
            'domain' => 'example.com',
            'max_age' => $maxAge,
            'expires' => $expires !== '' ? $expires : null,
            'scan_timestamp' => $scanned,
        ], static fn(mixed $value): bool => $value !== null));
    }
}
