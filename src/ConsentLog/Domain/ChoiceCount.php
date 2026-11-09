<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\Domain;

/**
 * How many choices of a month accepted the same categories, none for a withdrawal.
 */
final readonly class ChoiceCount
{
    /**
     * @param string $month The month, like 2026-11.
     * @param list<string> $categories The accepted categories.
     * @param int $count The number of choices.
     */
    public function __construct(
        private string $month,
        private array $categories,
        private int $count,
    ) {
    }

    public function month(): string
    {
        return $this->month;
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return $this->categories;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function isWithdrawal(): bool
    {
        return $this->categories === [];
    }
}
