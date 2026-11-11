<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support;

use ItalyCookieChoices\Console\IO\IOInterface;

/**
 * The terminal of the commands in the tests: it records what they write and answers the questions.
 */
final class FakeIO implements IOInterface
{
    /** @var list<string> */
    public array $lines = [];

    /** @var list<string> */
    public array $successes = [];

    /** @var list<string> */
    public array $errors = [];

    /** @var list<string> */
    public array $questions = [];

    /** @var list<array<string, string>> */
    public array $items = [];

    /** @var list<string> */
    public array $fields = [];

    public ?int $exit = null;

    /**
     * @param array<array-key, mixed> $arguments
     * @param array<array-key, mixed> $options
     * @param bool $confirms The answer to the questions.
     */
    public function __construct(
        private readonly array $arguments = [],
        private readonly array $options = [],
        private readonly bool $confirms = true,
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
        $this->lines[] = $text;
    }

    public function success(string $text): void
    {
        $this->successes[] = $text;
    }

    public function error(string $text): void
    {
        $this->errors[] = $text;
    }

    public function confirm(string $question): bool
    {
        $this->questions[] = $question;

        return $this->flag('yes') || $this->confirms;
    }

    public function items(array $items, array $fields): void
    {
        $this->items = $items;
        $this->fields = $fields;
    }

    public function exit(int $code): void
    {
        $this->exit = $code;
    }
}
