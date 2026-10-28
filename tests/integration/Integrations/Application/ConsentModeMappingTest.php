<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\Application;

use ItalyCookieChoices\Integrations\Application\BuildingConsentMode;
use ItalyCookieChoices\Integrations\Application\ConsentModeMapping;
use ItalyCookieChoices\Integrations\Domain\GoogleConsentType;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentModeMappingTest extends WPTestCase
{
    public function testEachCategoryGrantsItsConsentTypes(): void
    {
        $this->assertSame(
            [
                'necessary' => ['security_storage'],
                'preferences' => ['functionality_storage', 'personalization_storage'],
                'statistics' => ['analytics_storage'],
                'marketing' => ['ad_storage', 'ad_user_data', 'ad_personalization'],
            ],
            $this->makeSut()->toArray()
        );
    }

    public function testTheListenersMapTheCategories(): void
    {
        \add_action(BuildingConsentMode::class, static function (BuildingConsentMode $event): void {
            $event->mapCategory('social', GoogleConsentType::AD_STORAGE, GoogleConsentType::AD_USER_DATA);
            $event->mapCategory('preferences');
        });

        $mapping = $this->makeSut()->toArray();

        $this->assertSame(['ad_storage', 'ad_user_data'], $mapping['social']);
        $this->assertSame([], $mapping['preferences']);
    }

    private function makeSut(): ConsentModeMapping
    {
        return new ConsentModeMapping(new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState()));
    }
}
