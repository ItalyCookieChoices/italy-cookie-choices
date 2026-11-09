<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations;

use ItalyCookieChoices\Integrations\UI\AdvancedConsentMode;
use ItalyCookieChoices\Integrations\UI\ConsentModeScript;
use ItalyCookieChoices\Integrations\UI\MicrosoftConsentScript;
use ItalyCookieChoices\Integrations\UI\TrackingTags;
use ItalyCookieChoices\Integrations\UI\WpConsentApiFilters;
use ItalyCookieChoices\Integrations\UI\WpConsentApiScript;
use ItalyCookieChoices\Integrations\UI\YouTubeNoCookie;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;

/**
 * The integrations context: Google Consent Mode v2, the Microsoft consent signals, the WP Consent API,
 * the Google and Facebook tags and the YouTube embeds.
 */
final readonly class Module implements ModuleInterface
{
    private const BUILD = 'assets/build/';

    /**
     * @param string $pluginPath The plugin folder, with the trailing slash.
     * @param string $pluginUrl The plugin URL, with the trailing slash.
     */
    public function __construct(
        private string $pluginPath,
        private string $pluginUrl,
    ) {
    }

    public function __invoke(): iterable
    {
        return [
            AurynConfig::DEFINITIONS => [
                ConsentModeScript::class => [
                    ':buildPath' => $this->pluginPath . self::BUILD,
                ],
                MicrosoftConsentScript::class => [
                    ':buildPath' => $this->pluginPath . self::BUILD,
                    ':buildUrl' => $this->pluginUrl . self::BUILD,
                ],
                WpConsentApiScript::class => [
                    ':buildPath' => $this->pluginPath . self::BUILD,
                    ':buildUrl' => $this->pluginUrl . self::BUILD,
                ],
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                ConsentModeScript::class,
                TrackingTags::class,
                AdvancedConsentMode::class,
                YouTubeNoCookie::class,
                WpConsentApiFilters::class,
                WpConsentApiScript::class,
                MicrosoftConsentScript::class,
            ],
        ];
    }
}
