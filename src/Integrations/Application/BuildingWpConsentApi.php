<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\Application;

use ItalyCookieChoices\Integrations\Domain\WpConsentCategory;

/**
 * Dispatched before the consent is written to the WP Consent API, listeners map a category to the categories
 * of the API it allows:
 *
 * add_action( BuildingWpConsentApi::class, static function ( BuildingWpConsentApi $event ): void {
 *     $event->mapCategory( 'social', WpConsentCategory::MARKETING );
 * } );
 */
final class BuildingWpConsentApi
{
    /**
     * @param array<string, list<WpConsentCategory>> $mapping The categories of the API keyed by category id.
     */
    public function __construct(
        private array $mapping,
    ) {
    }

    /**
     * Replaces the categories of the API the category allows, none to allow nothing.
     */
    public function mapCategory(string $category, WpConsentCategory ...$categories): void
    {
        $this->mapping[$category] = \array_values($categories);
    }

    /**
     * @return array<string, list<WpConsentCategory>>
     */
    public function mapping(): array
    {
        return $this->mapping;
    }
}
