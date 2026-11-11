<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\Pipeline;

use ItalyCookieChoices\Console\IO\IOInterface;

/**
 * What runs through the pipeline of a command: the terminal and the values the middlewares find.
 */
final class Message
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    public function __construct(
        private readonly IOInterface $io,
    ) {
    }

    public function io(): IOInterface
    {
        return $this->io;
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }
}
