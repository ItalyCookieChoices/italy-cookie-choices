<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Application;

use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;

/**
 * The ids of the Google and Facebook tags in the code another plugin runs after the consent:
 * the plugin prints these tags itself from their ids.
 */
final readonly class TagIds
{
    /** The ids of the tags, as they appear in their code. */
    private const PATTERNS = [
        IntegrationSetting::GA_MEASUREMENT_ID => '/\b(G-[A-Z0-9]{4,})\b/',
        IntegrationSetting::GTM_CONTAINER_ID => '/\b(GTM-[A-Z0-9]{4,})\b/',
        IntegrationSetting::FACEBOOK_PIXEL_ID => '/fbq\(\s*[\'"]init[\'"]\s*,\s*[\'"](\d+)[\'"]/',
    ];

    /** The vendors whose tag the plugin prints. */
    private const VENDORS = [
        VendorId::GOOGLE_ANALYTICS,
        VendorId::GOOGLE_TAG_MANAGER,
        VendorId::FACEBOOK,
    ];

    /**
     * The ids found in the code, by the setting of the plugin that keeps them.
     *
     * @return array<string, string>
     */
    public function of(string $code): array
    {
        $ids = [];
        foreach (self::PATTERNS as $setting => $pattern) {
            if (\preg_match($pattern, $code, $match) === 1) {
                $ids[$setting] = $match[1];
            }
        }

        return $ids;
    }

    /**
     * The plugin prints the tag of the vendor, its code is not needed when its id is found.
     */
    public function isPrinted(string $vendor): bool
    {
        return \in_array($vendor, self::VENDORS, true);
    }
}
