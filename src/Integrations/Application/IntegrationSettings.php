<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\Application;

use ItalyCookieChoices\Settings\Options;

/**
 * The saved settings of the Google, Microsoft and Facebook tags.
 */
final readonly class IntegrationSettings
{
    public function __construct(
        private Options $options,
    ) {
    }

    public function sendsConsentMode(): bool
    {
        return $this->options->isActive() && $this->options->isChecked(IntegrationSetting::CONSENT_MODE);
    }

    /**
     * The Google tags load before the consent and send cookieless pings until it is granted.
     */
    public function isConsentModeAdvanced(): bool
    {
        return $this->sendsConsentMode() && $this->options->isChecked(IntegrationSetting::CONSENT_MODE_ADVANCED);
    }

    /**
     * Microsoft Advertising gets the consent of the visitor, through the UET tag.
     */
    public function sendsUetConsent(): bool
    {
        return $this->options->isActive() && $this->options->isChecked(IntegrationSetting::UET_CONSENT_MODE);
    }

    /**
     * Microsoft Clarity gets the consent of the visitor, through its Consent API v2.
     */
    public function sendsClarityConsent(): bool
    {
        return $this->options->isActive() && $this->options->isChecked(IntegrationSetting::CLARITY_CONSENT_MODE);
    }

    public function measurementId(): string
    {
        return $this->tagId(IntegrationSetting::GA_MEASUREMENT_ID);
    }

    public function containerId(): string
    {
        return $this->tagId(IntegrationSetting::GTM_CONTAINER_ID);
    }

    public function pixelId(): string
    {
        return $this->tagId(IntegrationSetting::FACEBOOK_PIXEL_ID);
    }

    public function usesYouTubeNoCookie(): bool
    {
        return $this->options->isActive() && $this->options->isChecked(IntegrationSetting::YOUTUBE_NOCOOKIE);
    }

    /**
     * The tags are printed only when the plugin is active, they need the consent.
     */
    private function tagId(string $name): string
    {
        return $this->options->isActive() ? $this->options->text($name) : '';
    }
}
