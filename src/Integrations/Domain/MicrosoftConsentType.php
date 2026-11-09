<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\Domain;

/**
 * The consent types of Microsoft Advertising (UET) and of the Microsoft Clarity Consent API v2.
 *
 * Clarity reads its keys case-sensitive, with the capital S.
 *
 * @link https://learn.microsoft.com/en-us/clarity/setup-and-installation/clarity-consent-api-v2
 */
enum MicrosoftConsentType: string
{
    case UET_AD_STORAGE = 'ad_storage';
    case CLARITY_AD_STORAGE = 'ad_Storage';
    case CLARITY_ANALYTICS_STORAGE = 'analytics_Storage';
}
