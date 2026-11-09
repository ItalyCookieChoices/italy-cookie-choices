<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\Domain;

/**
 * How the visitors answer the banner, by month, from the records of the consent log.
 *
 * A choice accepts all the optional categories, rejects them all, or accepts only some of them;
 * a record without categories is a withdrawal. It counts the choices, a visitor can make more than one.
 */
final readonly class ConsentStatistics
{
    /**
     * @param list<ChoiceCount> $choices
     * @param list<string> $optionalCategories The categories the visitor can accept or reject.
     */
    public function __construct(
        private array $choices,
        private array $optionalCategories,
    ) {
    }

    /**
     * @return list<array{
     *     month: string,
     *     choices: int,
     *     acceptAll: int,
     *     rejectAll: int,
     *     partial: int,
     *     withdrawals: int,
     *     categories: array<string, int>
     * }> The newest month first.
     */
    public function months(): array
    {
        $months = [];
        foreach ($this->choices as $choice) {
            $month = $choice->month();
            $months[$month] = $this->added($months[$month] ?? $this->empty($month), $choice);
        }

        \krsort($months);

        return \array_values($months);
    }

    /**
     * @return array{
     *     month: string,
     *     choices: int,
     *     acceptAll: int,
     *     rejectAll: int,
     *     partial: int,
     *     withdrawals: int,
     *     categories: array<string, int>
     * }
     */
    private function empty(string $month): array
    {
        return [
            'month' => $month,
            'choices' => 0,
            'acceptAll' => 0,
            'rejectAll' => 0,
            'partial' => 0,
            'withdrawals' => 0,
            'categories' => \array_fill_keys($this->optionalCategories, 0),
        ];
    }

    /**
     * @param array{
     *     month: string,
     *     choices: int,
     *     acceptAll: int,
     *     rejectAll: int,
     *     partial: int,
     *     withdrawals: int,
     *     categories: array<string, int>
     * } $month
     * @return array{
     *     month: string,
     *     choices: int,
     *     acceptAll: int,
     *     rejectAll: int,
     *     partial: int,
     *     withdrawals: int,
     *     categories: array<string, int>
     * }
     */
    private function added(array $month, ChoiceCount $choice): array
    {
        $month['choices'] += $choice->count();
        $month[$this->kind($choice)] += $choice->count();
        foreach ($this->accepted($choice) as $category) {
            $month['categories'][$category] += $choice->count();
        }

        return $month;
    }

    /**
     * @return 'acceptAll'|'rejectAll'|'partial'|'withdrawals'
     */
    private function kind(ChoiceCount $choice): string
    {
        if ($choice->isWithdrawal()) {
            return 'withdrawals';
        }

        $accepted = $this->accepted($choice);
        if ($accepted === []) {
            return 'rejectAll';
        }

        return \count($accepted) === \count($this->optionalCategories) ? 'acceptAll' : 'partial';
    }

    /**
     * @return list<string> The optional categories of the policy the choice accepted.
     */
    private function accepted(ChoiceCount $choice): array
    {
        return \array_values(\array_intersect($this->optionalCategories, $choice->categories()));
    }
}
