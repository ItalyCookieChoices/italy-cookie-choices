<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\Application;

use ItalyCookieChoices\Consent\Application\BuildingConsentPolicy;
use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\Domain\ConsentDuration;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentPolicyFactoryTest extends WPTestCase
{
    use BuildsOptions;

    public function testTheDefaultPolicy(): void
    {
        $policy = $this->makeSut()->create();

        $this->assertSame(['necessary', 'preferences', 'statistics', 'marketing'], $policy->categories()->ids());
        $this->assertSame(['necessary'], $policy->categories()->requiredIds());
        $this->assertSame('1', $policy->version()->toString());
        $this->assertSame(182, $policy->duration()->days());
    }

    public function testTheDurationIsTheSavedOne(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'consent_duration' => '365']);

        $this->assertSame(365, $this->makeSut()->create()->duration()->days());
    }

    public function testAnInvalidDurationIsTheDefaultOne(): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(Schema::OPTION, ['active' => '1', 'consent_duration' => 'never']);

        $this->assertSame(182, $this->makeSut()->create()->duration()->days());
    }

    public function testTheListenersChangeThePolicy(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'consent_duration' => '365']);
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

    public function testTheEventIsCountedLikeAnEventName(): void
    {
        $dispatched = \did_action(BuildingConsentPolicy::class);

        $this->makeSut()->create();

        $this->assertSame($dispatched + 1, \did_action(BuildingConsentPolicy::class));
    }

    private function makeSut(): ConsentPolicyFactory
    {
        return new ConsentPolicyFactory(
            new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState()),
            $this->options()
        );
    }
}
