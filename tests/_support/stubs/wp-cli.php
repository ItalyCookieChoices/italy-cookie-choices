<?php

/**
 * The WP-CLI symbols the commands use, for PHPStan: php-stubs/wp-cli-stubs needs the WordPress stubs before 7.
 *
 * @phpcs:disable
 */

declare(strict_types=1);

namespace {
    class WP_CLI
    {
        /**
         * @param callable|object|string $callable
         * @param array<string, mixed> $args
         * @phpstan-impure
         */
        public static function add_command(string $name, $callable, array $args = []): bool
        {
            return true;
        }

        public static function line(string $message = ''): void
        {
        }

        public static function success(string $message): void
        {
        }

        public static function warning(string $message): void
        {
        }

        /**
         * @param string $message
         * @param bool|int $exit
         * @return ($exit is true|positive-int ? never : void)
         */
        public static function error($message, $exit = true)
        {
        }

        /**
         * @param array<string, mixed> $assoc_args
         */
        public static function confirm(string $question, array $assoc_args = []): void
        {
        }

        public static function halt(int $return_code): never
        {
            exit($return_code);
        }
    }
}

namespace WP_CLI\Dispatcher {
    abstract class CommandNamespace
    {
    }
}

namespace WP_CLI\Utils {
    /**
     * @param array<array-key, mixed> $items
     * @param array<array-key, string>|string $fields
     */
    function format_items(string $format, array $items, $fields): void
    {
    }
}
