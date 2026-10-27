<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\Application;

use ItalyCookieChoices\Settings\Options;

/**
 * The saved settings of the Google and Facebook tags.
 */
final readonly class IntegrationSettings
{
    public function __construct(
        private Options $options,
    ) {
    }

    public function sendsConsentMode(): bool
    {
        return $this->options->isActive() && $this->options->isChecked('consent_mode');
    }

    /**
     * The Google tags load before the consent and send cookieless pings until it is granted.
     */
    public function isConsentModeAdvanced(): bool
    {
        return $this->sendsConsentMode() && $this->options->isChecked('consent_mode_advanced');
    }

    public function measurementId(): string
    {
        return $this->tagId('ga_measurement_id');
    }

    public function containerId(): string
    {
        return $this->tagId('gtm_container_id');
    }

    public function pixelId(): string
    {
        return $this->tagId('facebook_pixel_id');
    }

    public function usesYouTubeNoCookie(): bool
    {
        return $this->options->isActive() && $this->options->isChecked('youtube_nocookie');
    }

    /**
     * The tags are printed only when the plugin is active, they need the consent.
     */
    private function tagId(string $name): string
    {
        return $this->options->isActive() ? $this->options->text($name) : '';
    }
}
