<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent;

use ItalyCookieChoices\Consent\Domain\ConsentRepository;
use ItalyCookieChoices\Consent\Infrastructure\CookieConsentRepository;
use ItalyCookieChoices\Consent\UI\Banner;
use ItalyCookieChoices\Consent\UI\BannerPreviewEndpoint;
use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\Consent\UI\HasConsentFilter;
use ItalyCookieChoices\Consent\UI\PreferencesButtonBlock;
use ItalyCookieChoices\Consent\UI\PreferencesShortcode;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;

/**
 * The consent context: the categories, the consent cookie and its JavaScript API.
 */
final readonly class Module implements ModuleInterface
{
    private const BUILD = 'assets/build/';

    /**
     * @param string $pluginPath The plugin folder, with the trailing slash.
     * @param string $pluginUrl The plugin URL, with the trailing slash.
     * @param array<array-key, mixed> $cookies The request cookies.
     */
    public function __construct(
        private string $pluginPath,
        private string $pluginUrl,
        private array $cookies,
    ) {
    }

    public function __invoke(): iterable
    {
        return [
            AurynConfig::ALIASES => [
                ConsentRepository::class => CookieConsentRepository::class,
            ],
            AurynConfig::DEFINITIONS => [
                CookieConsentRepository::class => [
                    ':cookies' => $this->cookies,
                ],
                ConsentScript::class => [
                    ':buildPath' => $this->pluginPath . self::BUILD,
                    ':buildUrl' => $this->pluginUrl . self::BUILD,
                ],
                Banner::class => [
                    ':buildPath' => $this->pluginPath . self::BUILD,
                    ':buildUrl' => $this->pluginUrl . self::BUILD,
                ],
                PreferencesButtonBlock::class => [
                    ':buildPath' => $this->pluginPath . self::BUILD,
                ],
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                ConsentScript::class,
                Banner::class,
                HasConsentFilter::class,
                PreferencesShortcode::class,
                PreferencesButtonBlock::class,
                BannerPreviewEndpoint::class,
            ],
        ];
    }
}
