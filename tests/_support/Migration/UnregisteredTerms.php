<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Migration;

/**
 * Writes the terms of a taxonomy that is not registered, like the one of a deactivated plugin, with queries.
 */
final class UnregisteredTerms
{
    public static function insert(
        string $taxonomy,
        string $name,
        string $slug = '',
        string $description = '',
        int $parent = 0
    ): int {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $wpdb->insert($wpdb->terms, ['name' => $name, 'slug' => $slug !== '' ? $slug : \sanitize_title($name)]);
        $term = (int) $wpdb->insert_id;
        $wpdb->insert($wpdb->term_taxonomy, [
            'term_id' => $term,
            'taxonomy' => $taxonomy,
            'description' => $description,
            'parent' => $parent,
        ]);

        return $term;
    }

    public static function relate(int $object, int $term): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $taxonomy = $wpdb->get_var($wpdb->prepare(
            'SELECT term_taxonomy_id FROM %i WHERE term_id = %d',
            $wpdb->term_taxonomy,
            $term
        ));
        $wpdb->insert($wpdb->term_relationships, ['object_id' => $object, 'term_taxonomy_id' => (int) $taxonomy]);
    }
}
