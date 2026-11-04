<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\Application;

use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Integrations\Domain\WpConsentCategory;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The categories of the WP Consent API each category allows, after the listeners of BuildingWpConsentApi.
 */
final readonly class WpConsentApiMapping
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @return array<string, list<string>> The category values of the API keyed by category id.
     */
    public function toArray(): array
    {
        $event = new BuildingWpConsentApi([
            Category::NECESSARY => [WpConsentCategory::FUNCTIONAL],
            Category::PREFERENCES => [WpConsentCategory::PREFERENCES],
            Category::STATISTICS => [WpConsentCategory::STATISTICS, WpConsentCategory::STATISTICS_ANONYMOUS],
            Category::MARKETING => [WpConsentCategory::MARKETING],
        ]);
        $this->dispatcher->dispatch($event);

        return \array_map(
            static fn(array $categories): array => \array_map(
                static fn(WpConsentCategory $category): string => $category->value,
                $categories
            ),
            $event->mapping()
        );
    }

    /**
     * @param string $apiCategory A category of the WP Consent API.
     * @return list<string> The ids of the categories that allow it, none when it is not mapped.
     */
    public function categoriesAllowing(string $apiCategory): array
    {
        $allowing = \array_filter(
            $this->toArray(),
            static fn(array $apiCategories): bool => \in_array($apiCategory, $apiCategories, true)
        );

        return \array_map('strval', \array_keys($allowing));
    }
}
