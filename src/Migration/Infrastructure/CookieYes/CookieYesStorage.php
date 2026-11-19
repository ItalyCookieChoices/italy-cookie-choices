<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\CookieYes;

/**
 * Reads the settings CookieYes keeps in the database, also when it is deactivated.
 *
 * Version 3 keeps the banners, the categories and the cookies in tables, with JSON columns: the texts by language.
 * The versions 2 keep an option and the cookies as posts, CookieYes 3 still runs them until they are upgraded.
 */
final readonly class CookieYesStorage
{
    private const SETTINGS = 'cky_settings';
    private const CONSENT_MODE = 'cky_gcm_settings';
    private const DATABASE_VERSION = 'cky_cookie_consent_lite_db_version';
    private const LEGACY_DATABASE_VERSION = '2.0';
    private const LEGACY_OPTIONS = 'CookieLawInfo-0.9';
    private const LEGACY_COOKIES = 'cookielawinfo';
    private const BANNER_TABLE = 'cky_banners';
    private const CATEGORY_TABLE = 'cky_cookie_categories';
    private const COOKIE_TABLE = 'cky_cookies';
    private const TABLE_VERSION = 'cky_%s_table_version';

    public function __construct(
        private \wpdb $database,
    ) {
    }

    public function hasSettings(): bool
    {
        return $this->legacyOptions() !== [] || $this->hasTable('banners');
    }

    /**
     * Whether the site still runs the storage of the versions 2, not upgraded.
     */
    public function isLegacy(): bool
    {
        $version = \get_option(self::DATABASE_VERSION, self::LEGACY_DATABASE_VERSION);

        return $this->legacyOptions() !== []
            && \version_compare(\is_string($version) ? $version : '', self::LEGACY_DATABASE_VERSION, '<=');
    }

    /**
     * @return array<array-key, mixed>
     */
    public function settings(): array
    {
        return $this->arrayOption(self::SETTINGS);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function consentMode(): array
    {
        return $this->arrayOption(self::CONSENT_MODE);
    }

    /**
     * The settings and the texts of the active banner, decoded.
     *
     * @return array{settings: array<array-key, mixed>, contents: array<array-key, mixed>}
     */
    public function banner(): array
    {
        $empty = ['settings' => [], 'contents' => []];
        if (!$this->hasTable('banners')) {
            return $empty;
        }

        $row = $this->database->get_row($this->database->prepare(
            'SELECT settings, contents FROM %i WHERE status = 1 ORDER BY banner_id ASC LIMIT 1',
            $this->database->prefix . self::BANNER_TABLE
        ), \ARRAY_A);

        if (!\is_array($row)) {
            return $empty;
        }

        return [
            'settings' => $this->decoded($row['settings'] ?? ''),
            'contents' => $this->decoded($row['contents'] ?? ''),
        ];
    }

    /**
     * @return list<array{id: int, slug: string, name: array<array-key, mixed>, description: array<array-key, mixed>}>
     */
    public function categories(): array
    {
        if (!$this->hasTable('cookie_category')) {
            return [];
        }

        $rows = $this->database->get_results($this->database->prepare(
            'SELECT category_id, slug, name, description FROM %i ORDER BY category_id ASC',
            $this->database->prefix . self::CATEGORY_TABLE
        ), \ARRAY_A);

        return \array_map(
            fn(array $row): array => [
                'id' => (int) $this->text($row['category_id'] ?? '0'),
                'slug' => $this->text($row['slug'] ?? ''),
                'name' => $this->decoded($row['name'] ?? ''),
                'description' => $this->decoded($row['description'] ?? ''),
            ],
            $rows ?? []
        );
    }

    /**
     * @return list<array{
     *     name: string,
     *     category: int,
     *     duration: array<array-key, mixed>,
     *     description: array<array-key, mixed>,
     *     pattern: string
     * }>
     */
    public function cookies(): array
    {
        if (!$this->hasTable('cookie')) {
            return [];
        }

        $rows = $this->database->get_results($this->database->prepare(
            'SELECT name, category, duration, description, url_pattern FROM %i ORDER BY cookie_id ASC',
            $this->database->prefix . self::COOKIE_TABLE
        ), \ARRAY_A);

        return \array_map(
            fn(array $row): array => [
                'name' => $this->text($row['name'] ?? ''),
                'category' => (int) $this->text($row['category'] ?? '0'),
                'duration' => $this->decoded($row['duration'] ?? ''),
                'description' => $this->decoded($row['description'] ?? ''),
                'pattern' => $this->text($row['url_pattern'] ?? ''),
            ],
            $rows ?? []
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    public function legacyOptions(): array
    {
        return $this->arrayOption(self::LEGACY_OPTIONS);
    }

    /**
     * The cookies of the versions 2, published posts with the duration and the sensitivity in their meta.
     *
     * @return list<array{name: string, duration: string, purpose: string, sensitivity: string}>
     */
    public function legacyCookies(): array
    {
        $posts = \get_posts([
            'post_type' => self::LEGACY_COOKIES,
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'suppress_filters' => true,
        ]);

        return \array_map(
            fn(\WP_Post $post): array => [
                'name' => $post->post_title,
                'duration' => $this->text(\get_post_meta($post->ID, '_cli_cookie_duration', true)),
                'purpose' => \wp_strip_all_tags($post->post_content),
                'sensitivity' => $this->text(\get_post_meta($post->ID, '_cli_cookie_sensitivity', true)),
            ],
            \array_values($posts)
        );
    }

    private function hasTable(string $table): bool
    {
        return \get_option(\sprintf(self::TABLE_VERSION, $table)) !== false;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function arrayOption(string $name): array
    {
        $option = \get_option($name);

        return \is_array($option) ? $option : [];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decoded(mixed $json): array
    {
        $decoded = \is_string($json) ? \json_decode($json, true) : null;

        return \is_array($decoded) ? $decoded : [];
    }

    private function text(mixed $value): string
    {
        return \is_scalar($value) ? \trim((string) $value) : '';
    }
}
