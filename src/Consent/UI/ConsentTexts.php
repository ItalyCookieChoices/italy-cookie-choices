<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Settings\Options;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The texts of the banner and the labels of the categories, after the listeners of BuildingBanner.
 */
final readonly class ConsentTexts
{
    public function __construct(
        private ConsentPolicyFactory $policy,
        private EventDispatcherInterface $dispatcher,
        private Options $options,
    ) {
    }

    /**
     * @return array{
     *     texts: array<string, string>,
     *     categories: list<array{id: string, required: bool, label: string, description: string}>
     * }
     */
    public function all(): array
    {
        $event = new BuildingBanner($this->defaultTexts(), $this->defaultCategoryLabels());
        $this->dispatcher->dispatch($event);

        $categories = [];
        foreach ($this->policy->create()->categories() as $category) {
            $categories[] = [
                'id' => $category->id(),
                'required' => $category->isRequired(),
                ...$event->categoryLabel($category),
            ];
        }

        return ['texts' => $event->texts(), 'categories' => $categories];
    }

    /**
     * @return array<string, string>
     */
    private function defaultTexts(): array
    {
        return [
            BuildingBanner::TITLE => \__('Cookie consent', 'italy-cookie-choices'),
            BuildingBanner::ACCEPT_ALL => $this->options->translated(
                'Banner button text',
                'button_text',
                \__('Accept all', 'italy-cookie-choices')
            ),
            BuildingBanner::REJECT_ALL => \__('Reject all', 'italy-cookie-choices'),
            BuildingBanner::PREFERENCES => \__('Preferences', 'italy-cookie-choices'),
            BuildingBanner::CLOSE => \__('Close and keep only the necessary cookies', 'italy-cookie-choices'),
            BuildingBanner::PREFERENCES_TITLE => \__('Cookie preferences', 'italy-cookie-choices'),
            BuildingBanner::SAVE => \__('Save preferences', 'italy-cookie-choices'),
            BuildingBanner::CLOSE_PREFERENCES => \__('Close the preferences', 'italy-cookie-choices'),
        ];
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    private function defaultCategoryLabels(): array
    {
        return [
            Category::NECESSARY => [
                'label' => \__('Necessary', 'italy-cookie-choices'),
                'description' => \__(
                    'Needed for the site to work, like the cookie that stores this choice. Always active.',
                    'italy-cookie-choices'
                ),
            ],
            Category::PREFERENCES => [
                'label' => \__('Preferences', 'italy-cookie-choices'),
                'description' => \__('Remember your choices, like the language or the region.', 'italy-cookie-choices'),
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
        ];
    }
}
