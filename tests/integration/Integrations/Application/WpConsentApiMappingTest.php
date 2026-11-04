<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\Application;

use ItalyCookieChoices\Integrations\Application\BuildingWpConsentApi;
use ItalyCookieChoices\Integrations\Application\WpConsentApiMapping;
use ItalyCookieChoices\Integrations\Domain\WpConsentCategory;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class WpConsentApiMappingTest extends WPTestCase
{
    public function testEachCategoryAllowsItsWpConsentApiCategories(): void
    {
        $this->assertSame(
            [
                'necessary' => ['functional'],
                'preferences' => ['preferences'],
                'statistics' => ['statistics', 'statistics-anonymous'],
                'marketing' => ['marketing'],
            ],
            $this->makeSut()->toArray()
        );
    }

    public function testTheListenersMapTheCategories(): void
    {
        \add_action(BuildingWpConsentApi::class, static function (BuildingWpConsentApi $event): void {
            $event->mapCategory('social', WpConsentCategory::MARKETING);
            $event->mapCategory('statistics', WpConsentCategory::STATISTICS);
        });

        $mapping = $this->makeSut()->toArray();

        $this->assertSame(['marketing'], $mapping['social']);
        $this->assertSame(['statistics'], $mapping['statistics']);
    }

    public function testItFindsTheCategoriesThatAllowAWpConsentApiCategory(): void
    {
        \add_action(BuildingWpConsentApi::class, static function (BuildingWpConsentApi $event): void {
            $event->mapCategory('social', WpConsentCategory::MARKETING);
        });

        $sut = $this->makeSut();

        $this->assertSame(['marketing', 'social'], $sut->categoriesAllowing('marketing'));
        $this->assertSame(['statistics'], $sut->categoriesAllowing('statistics-anonymous'));
        $this->assertSame([], $sut->categoriesAllowing('unknown'));
    }

    private function makeSut(): WpConsentApiMapping
    {
        return new WpConsentApiMapping(new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState()));
    }
}
