<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cookies;

use ItalyCookieChoices\Cookies\UI\CookieTableBlock;
use ItalyCookieChoices\Cookies\UI\CookieTableShortcode;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;

/**
 * The cookies context: the cookies of the site and the cookie table of the cookie policy.
 */
final readonly class Module implements ModuleInterface
{
    private const BUILD = 'assets/build/';

    /**
     * @param string $pluginPath The plugin folder, with the trailing slash.
     */
    public function __construct(
        private string $pluginPath,
    ) {
    }

    public function __invoke(): iterable
    {
        return [
            AurynConfig::DEFINITIONS => [
                CookieTableBlock::class => [
                    ':buildPath' => $this->pluginPath . self::BUILD,
                    ':languagesPath' => $this->pluginPath . 'languages',
                ],
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                CookieTableShortcode::class,
                CookieTableBlock::class,
            ],
        ];
    }
}
