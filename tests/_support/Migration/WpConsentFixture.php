<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Migration;

/**
 * The settings, the categories, the services and the cookies of WPConsent, written for the tests
 * from its structure. Its post type and its taxonomy are not registered, like when it is deactivated.
 */
final class WpConsentFixture
{
    private const TAXONOMY = 'wpconsent_category';

    /**
     * @param array<string, mixed> $settings
     */
    public static function settings(array $settings): void
    {
        \update_option('wpconsent_settings', $settings);
    }

    public static function category(string $slug, string $name, string $description): int
    {
        return UnregisteredTerms::insert(self::TAXONOMY, $name, $slug, $description);
    }

    public static function service(int $category, string $name, string $slug = ''): int
    {
        return UnregisteredTerms::insert(self::TAXONOMY, $name, $slug, '', $category);
    }

    public static function cookie(int $term, string $name, string $duration, string $description): int
    {
        $post = \wp_insert_post([
            'post_type' => 'wpconsent_cookie',
            'post_title' => $name,
            'post_content' => $description,
            'post_status' => 'publish',
        ]);
        \update_post_meta($post, 'wpconsent_cookie_id', $name);
        \update_post_meta($post, 'wpconsent_cookie_duration', $duration);
        UnregisteredTerms::relate($post, $term);

        return $post;
    }
}
