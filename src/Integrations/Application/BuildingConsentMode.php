<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\Application;

use ItalyCookieChoices\Integrations\Domain\GoogleConsentType;

/**
 * Dispatched before the Consent Mode script is printed, listeners map a category to the Google consent types
 * it grants:
 *
 * add_action( BuildingConsentMode::class, static function ( BuildingConsentMode $event ): void {
 *     $event->mapCategory( 'social', GoogleConsentType::AD_STORAGE, GoogleConsentType::AD_USER_DATA );
 * } );
 */
final class BuildingConsentMode
{
    /**
     * @param array<string, list<GoogleConsentType>> $mapping The consent types keyed by category id.
     */
    public function __construct(
        private array $mapping,
    ) {
    }

    /**
     * Replaces the consent types the category grants, none to grant nothing.
     */
    public function mapCategory(string $category, GoogleConsentType ...$types): void
    {
        $this->mapping[$category] = \array_values($types);
    }

    /**
     * @return array<string, list<GoogleConsentType>>
     */
    public function mapping(): array
    {
        return $this->mapping;
    }
}
