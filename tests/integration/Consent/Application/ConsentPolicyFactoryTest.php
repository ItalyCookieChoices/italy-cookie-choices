<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\Application;

use ItalyCookieChoices\Consent\Application\BuildingConsentPolicy;
use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\Domain\ConsentDuration;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentPolicyFactoryTest extends WPTestCase
{
    public function testTheDefaultPolicy(): void
    {
        $policy = $this->makeSut()->create();

        $this->assertSame(['necessary', 'preferences', 'statistics', 'marketing'], $policy->categories()->ids());
        $this->assertSame(['necessary'], $policy->categories()->requiredIds());
        $this->assertSame('1', $policy->version()->toString());
        $this->assertSame(182, $policy->duration()->days());
    }

    public function testTheListenersChangeThePolicy(): void
    {
        \add_action(BuildingConsentPolicy::class, static function (BuildingConsentPolicy $event): void {
            $event->removeCategory(Category::PREFERENCES);
            $event->addCategory(new Category('social', true));
            $event->changeVersion(new PolicyVersion('2'));
            $event->changeDuration(new ConsentDuration(30));
        });

        $policy = $this->makeSut()->create();

        $this->assertSame(['necessary', 'statistics', 'marketing', 'social'], $policy->categories()->ids());
        $this->assertSame(['necessary', 'social'], $policy->categories()->requiredIds());
        $this->assertSame('2', $policy->version()->toString());
        $this->assertSame(30, $policy->duration()->days());
    }

    public function testTheEventIsCountedLikeAWordPressHook(): void
    {
        $dispatched = \did_action(BuildingConsentPolicy::class);

        $this->makeSut()->create();

        $this->assertSame($dispatched + 1, \did_action(BuildingConsentPolicy::class));
    }

    private function makeSut(): ConsentPolicyFactory
    {
        return new ConsentPolicyFactory(new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState()));
    }
}
