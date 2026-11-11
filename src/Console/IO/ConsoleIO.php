<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\IO;

/**
 * The terminal of WP-CLI.
 */
final readonly class ConsoleIO implements IOInterface
{
    private const YES = 'yes';
    private const FORMAT = 'format';
    private const TABLE = 'table';

    /**
     * @param array<array-key, mixed> $arguments
     * @param array<array-key, mixed> $options
     */
    public function __construct(
        private array $arguments,
        private array $options,
    ) {
    }

    public function argument(int $position): ?string
    {
        $argument = $this->arguments[$position] ?? null;

        return \is_string($argument) ? $argument : null;
    }

    public function option(string $name): ?string
    {
        $option = $this->options[$name] ?? null;

        return \is_string($option) ? $option : null;
    }

    public function flag(string $name): bool
    {
        return ($this->options[$name] ?? false) === true;
    }

    public function line(string $text): void
    {
        \WP_CLI::line($text);
    }

    public function success(string $text): void
    {
        \WP_CLI::success($text);
    }

    public function error(string $text): void
    {
        \WP_CLI::error($text, false);
    }

    /**
     * WP-CLI stops the command when the user does not confirm.
     */
    public function confirm(string $question): bool
    {
        if (!$this->flag(self::YES)) {
            \WP_CLI::confirm($question);
        }

        return true;
    }

    public function items(array $items, array $fields): void
    {
        \WP_CLI\Utils\format_items($this->option(self::FORMAT) ?? self::TABLE, $items, $fields);
    }

    public function exit(int $code): void
    {
        \WP_CLI::halt($code);
    }
}
