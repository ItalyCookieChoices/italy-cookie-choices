<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\Application;

/**
 * The names of the settings of the integrations, in the option of the plugin.
 */
final class IntegrationSetting
{
    public const CONSENT_MODE = 'consent_mode';
    public const CONSENT_MODE_ADVANCED = 'consent_mode_advanced';
    public const UET_CONSENT_MODE = 'uet_consent_mode';
    public const CLARITY_CONSENT_MODE = 'clarity_consent_mode';
    public const GA_MEASUREMENT_ID = 'ga_measurement_id';
    public const GTM_CONTAINER_ID = 'gtm_container_id';
    public const FACEBOOK_PIXEL_ID = 'facebook_pixel_id';
    public const YOUTUBE_NOCOOKIE = 'youtube_nocookie';
}
