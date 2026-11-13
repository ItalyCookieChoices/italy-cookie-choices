<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual;

use ItalyCookieChoices\Multilingual\Application\ConnectedSites;
use ItalyCookieChoices\Multilingual\Application\Translator;
use ItalyCookieChoices\Multilingual\Infrastructure\MultilingualPressSites;
use ItalyCookieChoices\Multilingual\Infrastructure\MultilingualPressTranslator;
use ItalyCookieChoices\Multilingual\UI\ConnectionNotice;
use ItalyCookieChoices\Multilingual\UI\SiteTextsEndpoint;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;

/**
 * The multilingual context: the texts of the sites of a network connected as translations by MultilingualPress,
 * edited from one settings page, filled and translated from a connected site.
 */
final readonly class Module implements ModuleInterface
{
    public function __invoke(): iterable
    {
        return [
            AurynConfig::ALIASES => [
                ConnectedSites::class => MultilingualPressSites::class,
                Translator::class => MultilingualPressTranslator::class,
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                SiteTextsEndpoint::class,
                ConnectionNotice::class,
            ],
        ];
    }
}
