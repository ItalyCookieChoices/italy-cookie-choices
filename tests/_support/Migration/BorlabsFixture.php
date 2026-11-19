<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Migration;

/**
 * The options and the tables of Borlabs Cookie 3, written for the tests from its documented structure.
 *
 * Its options are serialized objects of its classes: here they are objects of classes that do not exist,
 * like on a site where Borlabs Cookie is deactivated. The tables are temporary in the integration tests.
 */
final class BorlabsFixture
{
    public const LANGUAGE = 'en';

    public static function install(): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        \update_option('BorlabsCookieVersion', '3.3.0');
        $prefix = $wpdb->prefix . 'borlabs_cookie_';
        $tables = [
            'service_groups' => 'id int(11) NOT NULL AUTO_INCREMENT, `key` varchar(64), name text, description text,
                language varchar(16), status int(1) DEFAULT 1, PRIMARY KEY (id)',
            'services' => 'id int(11) NOT NULL AUTO_INCREMENT, `key` varchar(64), name text, service_group_id int(11),
                opt_in_code longtext, settings_fields longtext, language varchar(16), status int(1) DEFAULT 1,
                PRIMARY KEY (id)',
            'service_cookies' => 'id int(11) NOT NULL AUTO_INCREMENT, service_id int(11), name text, lifetime text,
                description text, PRIMARY KEY (id)',
            'service_locations' => 'id int(11) NOT NULL AUTO_INCREMENT, service_id int(11), hostname text,
                path text, PRIMARY KEY (id)',
        ];
        foreach ($tables as $name => $columns) {
            $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$name}");
            $wpdb->query("CREATE TABLE {$prefix}{$name} ($columns)");
        }
    }

    /**
     * Saves a config of Borlabs Cookie, like GeneralConfig, as the serialized object of a missing class.
     *
     * @param array<string, mixed> $properties
     */
    public static function config(string $name, array $properties): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $class = 'Borlabs\\Cookie\\Dto\\Config\\Missing' . $name;
        $object = \serialize((object) $properties);
        $object = 'O:' . \strlen($class) . ':"' . $class . '"' . \substr($object, \strlen('O:8:"stdClass"'));
        $option = 'BorlabsCookie' . $name . '_' . self::LANGUAGE;
        $wpdb->delete($wpdb->options, ['option_name' => $option]);
        $wpdb->insert($wpdb->options, ['option_name' => $option, 'option_value' => $object, 'autoload' => 'no']);
        \wp_cache_delete($option, 'options');
        \wp_cache_delete('alloptions', 'options');
    }

    public static function group(string $key, string $name, string $description): int
    {
        return self::insert('service_groups', [
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'language' => self::LANGUAGE,
        ]);
    }

    /**
     * @param array<string, string> $fields
     */
    public static function service(string $key, string $name, int $group, array $fields = [], string $optIn = ''): int
    {
        $list = [];
        foreach ($fields as $field => $value) {
            $list[] = ['key' => $field, 'value' => $value];
        }

        return self::insert('services', [
            'key' => $key,
            'name' => $name,
            'service_group_id' => $group,
            'opt_in_code' => $optIn,
            'settings_fields' => (string) \wp_json_encode(['list' => $list]),
            'language' => self::LANGUAGE,
        ]);
    }

    public static function cookie(int $service, string $name, string $lifetime, string $description): void
    {
        self::insert('service_cookies', [
            'service_id' => $service,
            'name' => $name,
            'lifetime' => $lifetime,
            'description' => $description,
        ]);
    }

    public static function location(int $service, string $hostname, string $path = ''): void
    {
        self::insert('service_locations', ['service_id' => $service, 'hostname' => $hostname, 'path' => $path]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function insert(string $table, array $row): int
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $wpdb->insert($wpdb->prefix . 'borlabs_cookie_' . $table, $row);

        return (int) $wpdb->insert_id;
    }
}
