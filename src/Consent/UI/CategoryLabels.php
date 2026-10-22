<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Consent\Domain\Category;

/**
 * The names and descriptions of the categories shown to the visitor.
 */
final readonly class CategoryLabels
{
    public const FILTER = 'italy_cookie_choices_category_labels';

    /**
     * @return array{label: string, description: string}
     */
    public function for(Category $category): array
    {
        $labels = $this->all();
        $label = $labels[$category->id()] ?? null;

        return [
            'label' => \is_array($label) && \is_string($label['label'] ?? null) ? $label['label'] : $category->id(),
            'description' => \is_array($label) && \is_string($label['description'] ?? null)
                ? $label['description']
                : '',
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function all(): array
    {
        /**
         * Filters the names and descriptions of the categories, keyed by category id.
         *
         * @param mixed $labels Like ['statistics' => ['label' => 'Statistics', 'description' => '...']].
         */
        $labels = \apply_filters(self::FILTER, [
            Category::NECESSARY => [
                'label' => \__('Necessary', 'italy-cookie-choices'),
                'description' => \__(
                    'Needed for the site to work, like the cookie that stores this choice. Always active.',
                    'italy-cookie-choices'
                ),
            ],
            Category::PREFERENCES => [
                'label' => \__('Preferences', 'italy-cookie-choices'),
                'description' => \__(
                    'Remember your choices, like the language or the region.',
                    'italy-cookie-choices'
                ),
            ],
            Category::STATISTICS => [
                'label' => \__('Statistics', 'italy-cookie-choices'),
                'description' => \__(
                    'Help to understand how the site is used, collecting anonymous data.',
                    'italy-cookie-choices'
                ),
            ],
            Category::MARKETING => [
                'label' => \__('Marketing', 'italy-cookie-choices'),
                'description' => \__(
                    'Used by third parties, like videos, maps and social networks, to show content and ads.',
                    'italy-cookie-choices'
                ),
            ],
        ]);

        return \is_array($labels) ? $labels : [];
    }
}
