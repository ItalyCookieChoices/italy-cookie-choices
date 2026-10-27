<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\UI;

use ItalyCookieChoices\Blocking\Application\BuildingVendors;
use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyStrap\Event\SubscriberInterface;

/**
 * In the advanced Consent Mode the Google tags are not blocked, they read the denied signals instead.
 */
final readonly class AdvancedConsentMode implements SubscriberInterface
{
    public const GOOGLE_VENDORS = ['google-analytics', 'google-tag-manager'];

    public function __construct(
        private IntegrationSettings $settings,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield BuildingVendors::class => 'unblockGoogleTags';
    }

    public function unblockGoogleTags(BuildingVendors $event): void
    {
        if (!$this->settings->isConsentModeAdvanced()) {
            return;
        }

        foreach (self::GOOGLE_VENDORS as $vendor) {
            $event->removeVendor($vendor);
        }
    }
}
