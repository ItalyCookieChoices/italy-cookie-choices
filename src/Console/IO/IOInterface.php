<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\IO;

/**
 * What a command reads from the terminal and writes to it.
 */
interface IOInterface
{
    /**
     * The positional argument, null when it is missing.
     */
    public function argument(int $position): ?string;

    /**
     * The value of an option like --file=<path>, null when it is missing or it is a flag.
     */
    public function option(string $name): ?string;

    /**
     * Whether a flag like --yes is set.
     */
    public function flag(string $name): bool;

    public function line(string $text): void;

    public function success(string $text): void;

    /**
     * Writes the error, the exit code is given by exit().
     */
    public function error(string $text): void;

    /**
     * True when the user confirms, or with the --yes flag.
     */
    public function confirm(string $question): bool;

    /**
     * Prints a list in the format of the --format option, a table by default.
     *
     * @param list<array<string, string>> $items
     * @param list<string> $fields
     */
    public function items(array $items, array $fields): void;

    public function exit(int $code): void;
}
