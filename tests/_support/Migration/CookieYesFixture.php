<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Migration;

/**
 * The options and the tables of CookieYes 3, with the columns the import reads, and the storage of 2.x.
 * In the integration tests the tables are temporary, dropped at the end of each test.
 */
final class CookieYesFixture
{
    /**
     * @param array<string, mixed> $settings The settings column of the active banner.
     * @param array<string, mixed> $contents The contents column of the active banner, by language.
     */
    public static function install(array $settings, array $contents): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        \update_option('cky_settings', ['languages' => ['selected' => ['en', 'it'], 'default' => 'en']]);
        \update_option('cky_cookie_consent_lite_db_version', '3.0');
        foreach (['banners', 'cookie', 'cookie_category'] as $table) {
            \update_option('cky_' . $table . '_table_version', '3.5.9');
        }

        $banners = $wpdb->prefix . 'cky_banners';
        $wpdb->query("DROP TABLE IF EXISTS $banners");
        $wpdb->query("CREATE TABLE $banners (
            banner_id bigint(20) NOT NULL AUTO_INCREMENT, name text, status int(11) NOT NULL DEFAULT 0,
            settings longtext, contents longtext, PRIMARY KEY (banner_id)
        )");
        $wpdb->insert($banners, ['name' => 'Off', 'status' => 0, 'settings' => '{}', 'contents' => '{}']);
        $wpdb->insert($banners, [
            'name' => 'GDPR',
            'status' => 1,
            'settings' => (string) \wp_json_encode($settings),
            'contents' => (string) \wp_json_encode($contents),
        ]);

        $categories = $wpdb->prefix . 'cky_cookie_categories';
        $wpdb->query("DROP TABLE IF EXISTS $categories");
        $wpdb->query("CREATE TABLE $categories (
            category_id bigint(20) NOT NULL AUTO_INCREMENT, name text, slug varchar(190), description longtext,
            PRIMARY KEY (category_id)
        )");

        $cookies = $wpdb->prefix . 'cky_cookies';
        $wpdb->query("DROP TABLE IF EXISTS $cookies");
        $wpdb->query("CREATE TABLE $cookies (
            cookie_id bigint(20) NOT NULL AUTO_INCREMENT, name varchar(190), description longtext,
            duration text, category bigint(20), url_pattern varchar(190), PRIMARY KEY (cookie_id)
        )");
    }

    /**
     * @param array<string, string> $name By language.
     * @param array<string, string> $description By language.
     */
    public static function addCategory(string $slug, array $name, array $description): int
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $wpdb->insert($wpdb->prefix . 'cky_cookie_categories', [
            'slug' => $slug,
            'name' => (string) \wp_json_encode($name),
            'description' => (string) \wp_json_encode($description),
        ]);

        return (int) $wpdb->insert_id;
    }

    public static function addCookie(
        string $name,
        int $category,
        string $duration,
        string $purpose,
        string $pattern = ''
    ): void {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $wpdb->insert($wpdb->prefix . 'cky_cookies', [
            'name' => $name,
            'category' => $category,
            'duration' => (string) \wp_json_encode(['en' => $duration]),
            'description' => (string) \wp_json_encode(['en' => $purpose]),
            'url_pattern' => $pattern,
        ]);
    }
}
