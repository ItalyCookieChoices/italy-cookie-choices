<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\RealCookieBanner;

/**
 * Reads the settings Real Cookie Banner keeps in the database, also when it is deactivated.
 *
 * Each setting is its own option, the texts have an option for each language with WPML or Polylang,
 * like rcb-banner-texts-headline-it. The services, the content blockers and the legal links are posts,
 * the service groups the terms of a taxonomy: they are read with queries, their types are not registered
 * when the plugin is deactivated. Only the structure of its data is read, nothing of it is used.
 *
 * @phpstan-type Post array{id: int, title: string, content: string}
 * @phpstan-type Group array{id: int, name: string, description: string, essential: bool, default: bool, order: int}
 */
final readonly class RealCookieBannerStorage
{
    public const SERVICES = 'rcb-cookie';
    public const BLOCKERS = 'rcb-blocker';
    public const LINKS = 'rcb-banner-link';

    private const VERSION = 'rcb_db_version';
    private const GROUPS = 'rcb-cookie-group';
    private const PUBLISHED = 'publish';

    public function __construct(
        private \wpdb $database,
    ) {
    }

    public function hasSettings(): bool
    {
        return \get_option(self::VERSION) !== false;
    }

    /**
     * An option of Real Cookie Banner, in the language of the site when it has one for each language.
     */
    public function option(string $name): mixed
    {
        $translated = \get_option($name . '-' . \substr(\get_locale(), 0, 2), null);

        return $translated ?? \get_option($name, null);
    }

    /**
     * The published posts of a type, in their order: the disabled services and blockers are not published.
     *
     * @return list<Post>
     */
    public function posts(string $type): array
    {
        if (!$this->hasSettings()) {
            return [];
        }

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

    public function meta(int $post, string $key): mixed
    {
        return \get_post_meta($post, $key, true);
    }

    /**
     * The service groups, with the marks of the essential group and of the groups created by the plugin.
     *
     * @return list<Group>
     */
    public function groups(): array
    {
        if (!$this->hasSettings()) {
            return [];
        }

        $rows = $this->database->get_results($this->database->prepare(
            'SELECT t.term_id, t.name, tt.description FROM %i t INNER JOIN %i tt ON tt.term_id = t.term_id'
                . ' WHERE tt.taxonomy = %s ORDER BY t.term_id ASC',
            $this->database->terms,
            $this->database->term_taxonomy,
            self::GROUPS
        ), \ARRAY_A);

        return \array_map(
            fn(array $row): array => $this->group($this->int($row['term_id'] ?? 0), $row),
            $rows ?? []
        );
    }

    /**
     * The id of the group of a service, 0 without one.
     */
    public function groupOf(int $post): int
    {
        $group = $this->database->get_var($this->database->prepare(
            'SELECT tt.term_id FROM %i tr INNER JOIN %i tt ON tt.term_taxonomy_id = tr.term_taxonomy_id'
                . ' WHERE tr.object_id = %d AND tt.taxonomy = %s ORDER BY tr.term_order ASC LIMIT 1',
            $this->database->term_relationships,
            $this->database->term_taxonomy,
            $post,
            self::GROUPS
        ));

        return \is_numeric($group) ? (int) $group : 0;
    }

    /**
     * @param array<array-key, mixed> $row
     * @return Group
     */
    private function group(int $id, array $row): array
    {
        return [
            'id' => $id,
            'name' => $this->string($row['name'] ?? ''),
            'description' => $this->string($row['description'] ?? ''),
            'essential' => \get_term_meta($id, 'isEssential', true) === '1',
            'default' => \get_term_meta($id, 'isDefault', true) === '1',
            'order' => $this->int(\get_term_meta($id, 'order', true)),
        ];
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
