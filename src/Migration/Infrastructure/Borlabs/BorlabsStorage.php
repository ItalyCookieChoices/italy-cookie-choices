<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\Borlabs;

/**
 * Reads the settings Borlabs Cookie 3 keeps in the database, also when it is deactivated.
 *
 * Its configs are options named after the config and the language, like BorlabsCookieGeneralConfig_en, saved as
 * serialized objects of its classes: they are read from the database and unserialized without creating any
 * object, then turned into arrays. The services, their groups, cookies and locations are in its tables, a row
 * for each language. Borlabs Cookie is a paid plugin: only the structure of its data is read.
 */
final readonly class BorlabsStorage
{
    private const VERSION = 'BorlabsCookieVersion';
    private const OPTION = 'BorlabsCookie%s_%s';
    private const LANGUAGES = 'BorlabsCookieGeneralConfig\\_%';
    private const TABLE = 'borlabs_cookie_%s';
    private const DEFAULT_LANGUAGE = 'en';

    public function __construct(
        private \wpdb $database,
    ) {
    }

    public function hasSettings(): bool
    {
        return \get_option(self::VERSION) !== false;
    }

    /**
     * A config of Borlabs Cookie in the language of the site, like GeneralConfig, as an array.
     *
     * @return array<array-key, mixed>
     */
    public function config(string $name): array
    {
        $raw = $this->database->get_var($this->database->prepare(
            'SELECT option_value FROM %i WHERE option_name = %s',
            $this->database->options,
            \sprintf(self::OPTION, $name, $this->language())
        ));
        $value = \is_string($raw) && \is_serialized($raw)
            ? @\unserialize($raw, ['allowed_classes' => false]) // phpcs:ignore
            : null;
        $config = $this->toArray($value);

        return \is_array($config) ? $config : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(string $table, bool $byLanguage): array
    {
        if (!$this->hasSettings()) {
            return [];
        }

        $table = \sprintf(self::TABLE, $table);
        $query = $byLanguage
            ? $this->database->prepare(
                'SELECT * FROM %i WHERE language = %s ORDER BY id ASC',
                $this->database->prefix . $table,
                $this->language()
            )
            : $this->database->prepare(
                'SELECT * FROM %i ORDER BY id ASC',
                $this->database->prefix . $table
            );

        $rows = $this->database->get_results($query, \ARRAY_A);

        return \array_map(
            static fn(array $row): array => \array_combine(\array_map('strval', \array_keys($row)), $row),
            $rows ?? []
        );
    }

    /**
     * The language of the site among the ones of Borlabs Cookie, or the first one.
     */
    private function language(): string
    {
        $languages = $this->database->get_col($this->database->prepare(
            'SELECT option_name FROM %i WHERE option_name LIKE %s',
            $this->database->options,
            self::LANGUAGES
        ));
        $codes = \array_map(
            static fn(string $name): string => \substr($name, \strrpos($name, '_') + 1),
            \array_filter($languages, \is_string(...))
        );
        $site = \substr(\get_locale(), 0, 2);
        foreach ([$site, self::DEFAULT_LANGUAGE] as $code) {
            if (\in_array($code, $codes, true)) {
                return $code;
            }
        }

        return \reset($codes) ?: self::DEFAULT_LANGUAGE;
    }

    /**
     * An unserialized value with the objects as arrays of their properties, the private ones too.
     */
    private function toArray(mixed $value): mixed
    {
        if (\is_object($value)) {
            $properties = [];
            foreach ((array) $value as $name => $property) {
                $name = (string) $name;
                $visible = \str_contains($name, "\0") ? \substr($name, \strrpos($name, "\0") + 1) : $name;
                $properties[$visible] = $property;
            }

            unset($properties['__PHP_Incomplete_Class_Name']);
            $value = $properties;
        }

        return \is_array($value) ? \array_map($this->toArray(...), $value) : $value;
    }
}
