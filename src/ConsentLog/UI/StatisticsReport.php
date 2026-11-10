<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI;

use ItalyCookieChoices\Consent\UI\ConsentTexts;
use ItalyCookieChoices\ConsentLog\Application\StatisticsFactory;

/**
 * The statistics of the consent log with the labels of the optional categories,
 * read by the Consent log tab and by the abilities.
 */
final readonly class StatisticsReport
{
    public function __construct(
        private StatisticsFactory $statistics,
        private ConsentTexts $texts,
    ) {
    }

    /**
     * @return array{categories: list<array{id: string, label: string}>, months: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'categories' => $this->optionalCategories(),
            'months' => $this->statistics->create()->months(),
        ];
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    private function optionalCategories(): array
    {
        $categories = [];
        foreach ($this->texts->all()['categories'] as $category) {
            if ($category['required']) {
                continue;
            }

            $categories[] = ['id' => $category['id'], 'label' => $category['label']];
        }

        return $categories;
    }
}
