<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure;

/**
 * Reads the posts and the terms of another plugin with queries: its post types and its taxonomies
 * are not registered when it is deactivated, so the functions of WordPress do not find them.
 *
 * @phpstan-type Post array{id: int, title: string, content: string}
 * @phpstan-type Term array{id: int, name: string, slug: string, description: string, parent: int}
 */
final readonly class UnregisteredContent
{
    private const PUBLISHED = 'publish';

    public function __construct(
        private \wpdb $database,
    ) {
    }

    /**
     * The published posts of a type, in their order.
     *
     * @return list<Post>
     */
    public function posts(string $type): array
    {
        $rows = $this->database->get_results($this->database->prepare(
            'SELECT ID, post_title, post_content FROM %i WHERE post_type = %s AND post_status = %s'
                . ' ORDER BY menu_order ASC, ID ASC',
            $this->database->posts,
            $type,
            self::PUBLISHED
        ), \ARRAY_A);

        return \array_map(
            fn(array $row): array => [
                'id' => $this->int($row['ID'] ?? 0),
                'title' => $this->string($row['post_title'] ?? ''),
                'content' => $this->string($row['post_content'] ?? ''),
            ],
            $rows ?? []
        );
    }

    /**
     * The terms of a taxonomy, in the order they were created.
     *
     * @return list<Term>
     */
    public function terms(string $taxonomy): array
    {
        $rows = $this->database->get_results($this->database->prepare(
            'SELECT t.term_id, t.name, t.slug, tt.description, tt.parent FROM %i t'
                . ' INNER JOIN %i tt ON tt.term_id = t.term_id WHERE tt.taxonomy = %s ORDER BY t.term_id ASC',
            $this->database->terms,
            $this->database->term_taxonomy,
            $taxonomy
        ), \ARRAY_A);

        return \array_map(
            fn(array $row): array => [
                'id' => $this->int($row['term_id'] ?? 0),
                'name' => $this->string($row['name'] ?? ''),
                'slug' => $this->string($row['slug'] ?? ''),
                'description' => $this->string($row['description'] ?? ''),
                'parent' => $this->int($row['parent'] ?? 0),
            ],
            $rows ?? []
        );
    }

    /**
     * The ids of the terms of a taxonomy a post has, in their order.
     *
     * @return list<int>
     */
    public function termsOf(int $post, string $taxonomy): array
    {
        $ids = $this->database->get_col($this->database->prepare(
            'SELECT tt.term_id FROM %i tr INNER JOIN %i tt ON tt.term_taxonomy_id = tr.term_taxonomy_id'
                . ' WHERE tr.object_id = %d AND tt.taxonomy = %s ORDER BY tr.term_order ASC',
            $this->database->term_relationships,
            $this->database->term_taxonomy,
            $post,
            $taxonomy
        ));

        return \array_map($this->int(...), $ids);
    }

    private function int(mixed $value): int
    {
        return \is_numeric($value) ? (int) $value : 0;
    }

    private function string(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : '';
    }
}
