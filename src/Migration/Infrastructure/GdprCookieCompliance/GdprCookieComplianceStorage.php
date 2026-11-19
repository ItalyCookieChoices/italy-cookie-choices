<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\GdprCookieCompliance;

/**
 * Reads the settings GDPR Cookie Compliance keeps in the database, also when it is deactivated.
 *
 * They are in its own table, one row for each setting, serialized: the scripts twice. The versions before
 * the table kept them in an option. The texts of a language of WPML or Polylang end with _ and its code.
 */
final readonly class GdprCookieComplianceStorage
{
    private const TABLE = 'gdpr_cc_options';
    private const TABLE_CREATED = 'gdpr_cc_db_created';
    private const LEGACY_OPTION = 'moove_gdpr_plugin_settings';

    public function __construct(
        private \wpdb $database,
    ) {
    }

    public function hasSettings(): bool
    {
        return $this->settings() !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $settings = \get_option(self::TABLE_CREATED) !== false ? $this->rows() : [];
        if ($settings !== []) {
            return $settings;
        }

        $legacy = \get_option(self::LEGACY_OPTION);
        $settings = [];
        foreach (\is_array($legacy) ? $legacy : [] as $key => $value) {
            $settings[(string) $key] = $this->unserialized($value);
        }

        return $settings;
    }

    /**
     * The code of the default language of Polylang or WPML, empty without them.
     */
    public function defaultLanguage(): string
    {
        $polylang = \get_option('polylang');
        $wpml = \get_option('icl_sitepress_settings');
        $language = \is_array($polylang) ? $polylang['default_lang'] ?? '' : '';
        $language = $language !== '' || !\is_array($wpml) ? $language : $wpml['default_language'] ?? '';

        return \is_string($language) ? $language : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function rows(): array
    {
        $rows = $this->database->get_results($this->database->prepare(
            'SELECT option_key, option_value FROM %i',
            $this->database->prefix . self::TABLE
        ), \ARRAY_A);

        $settings = [];
        foreach ($rows ?? [] as $row) {
            if (!\is_string($row['option_key'] ?? null)) {
                continue;
            }

            $settings[$row['option_key']] = $this->unserialized($this->unserialized($row['option_value'] ?? null));
        }

        return $settings;
    }

    /**
     * A serialized value, read without objects.
     */
    private function unserialized(mixed $value): mixed
    {
        if (!\is_string($value) || !\is_serialized($value)) {
            return $value;
        }

        $unserialized = @\unserialize($value, ['allowed_classes' => false]); // phpcs:ignore

        return $unserialized === false && $value !== \serialize(false) ? $value : $unserialized;
    }
}
