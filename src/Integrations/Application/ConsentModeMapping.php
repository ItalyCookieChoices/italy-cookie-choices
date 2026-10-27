<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\Application;

use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Integrations\Domain\GoogleConsentType;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The Google consent types each category grants, after the listeners of BuildingConsentMode.
 */
final readonly class ConsentModeMapping
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @return array<string, list<string>> The consent type values keyed by category id.
     */
    public function toArray(): array
    {
        $event = new BuildingConsentMode([
            Category::NECESSARY => [GoogleConsentType::SECURITY_STORAGE],
            Category::PREFERENCES => [
                GoogleConsentType::FUNCTIONALITY_STORAGE,
                GoogleConsentType::PERSONALIZATION_STORAGE,
            ],
            Category::STATISTICS => [GoogleConsentType::ANALYTICS_STORAGE],
            Category::MARKETING => [
                GoogleConsentType::AD_STORAGE,
                GoogleConsentType::AD_USER_DATA,
                GoogleConsentType::AD_PERSONALIZATION,
            ],
        ]);
        $this->dispatcher->dispatch($event);

        return \array_map(
            static fn(array $types): array => \array_map(
                static fn(GoogleConsentType $type): string => $type->value,
                $types
            ),
            $event->mapping()
        );
    }
}
