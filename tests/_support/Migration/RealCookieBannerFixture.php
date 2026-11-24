<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Migration;

/**
 * The options, the posts and the terms of Real Cookie Banner, written for the tests from its structure.
 *
 * Its post types and its taxonomy are not registered, like on a site where it is deactivated:
 * the terms and their relationships are written with queries.
 */
final class RealCookieBannerFixture
{
    public static function install(): void
    {
        \update_option('rcb_db_version', ['version' => '5.4.3']);
    }

    public static function option(string $name, mixed $value): void
    {
        \update_option('rcb-' . $name, $value);
    }

    public static function group(string $name, string $description, bool $essential, int $order): int
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $wpdb->insert($wpdb->terms, ['name' => $name, 'slug' => \sanitize_title($name)]);
        $term = (int) $wpdb->insert_id;
        $wpdb->insert($wpdb->term_taxonomy, [
            'term_id' => $term,
            'taxonomy' => 'rcb-cookie-group',
            'description' => $description,
        ]);
        \update_term_meta($term, 'isEssential', $essential ? '1' : '');
        \update_term_meta($term, 'isDefault', '1');
        \update_term_meta($term, 'order', (string) $order);

        return $term;
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function service(string $name, int $group, array $meta = [], string $status = 'publish'): int
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $service = self::post('rcb-cookie', $name, $meta, $status);
        $taxonomy = $wpdb->get_var($wpdb->prepare(
            'SELECT term_taxonomy_id FROM %i WHERE term_id = %d',
            $wpdb->term_taxonomy,
            $group
        ));
        $wpdb->insert($wpdb->term_relationships, ['object_id' => $service, 'term_taxonomy_id' => (int) $taxonomy]);

        return $service;
    }

    /**
     * @param list<string> $rules
     * @param list<int> $services
     */
    public static function blocker(string $name, array $rules, array $services): int
    {
        return self::post('rcb-blocker', $name, [
            'rules' => \implode("\n", $rules),
            'services' => \implode(',', $services),
        ]);
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function link(string $label, array $meta): int
    {
        return self::post('rcb-banner-link', $label, $meta);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private static function post(string $type, string $title, array $meta, string $status = 'publish'): int
    {
        $post = \wp_insert_post(['post_type' => $type, 'post_title' => $title, 'post_status' => $status]);
        foreach ($meta as $key => $value) {
            \update_post_meta($post, $key, \wp_slash(\is_array($value) ? (string) \wp_json_encode($value) : $value));
        }

        return $post;
    }
}
