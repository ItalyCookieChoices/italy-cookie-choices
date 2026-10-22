<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\Application;

use ItalyCookieChoices\Blocking\Application\BuildingVendors;
use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class VendorsFactoryTest extends WPTestCase
{
    public function testThePresetsAreBlockedByDefault(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $ids = $this->ids();

        $this->assertSame(
            \array_map(static fn(Vendor $vendor): string => $vendor->id(), (new VendorPresets())->all()),
            $ids
        );
        $this->assertNotContains(VendorsFactory::CUSTOM, $ids);
    }

    public function testTheSavedListsAreACustomMarketingVendor(): void
    {
        \update_option(Schema::OPTION, [
            'block_script' => ['tracker.example.com' => 'tracker.example.com'],
            'block_iframe' => ['widgets.example.com', ''],
            'block_embed' => ['tracker.example.com'],
        ]);

        $vendors = $this->makeSut()->create();

        $custom = $vendors->loading('https://widgets.example.com/frame');
        $this->assertNotNull($custom);
        $this->assertSame(VendorsFactory::CUSTOM, $custom->id());
        $this->assertSame('marketing', $custom->category());
        $this->assertSame(VendorsFactory::CUSTOM, $vendors->running('load("tracker.example.com")')?->id());
    }

    public function testTheSavedAllowedListsAreNeverBlocked(): void
    {
        \update_option(Schema::OPTION, [
            'allow_iframe' => ['youtube.com/embed/public'],
            'allow_script' => ['iccAllowed'],
            'block_script' => ['icc'],
        ]);

        $vendors = $this->makeSut()->create();

        $this->assertNull($vendors->loading('https://www.youtube.com/embed/public-video'));
        $this->assertNull($vendors->running('window.iccAllowed = true;'));
        $this->assertNotNull($vendors->running('window.iccBlocked = true;'));
    }

    public function testTheListenersChangeTheVendors(): void
    {
        \add_action(BuildingVendors::class, static function (BuildingVendors $event): void {
            $event->removeVendor('youtube');
            $event->addVendor(new Vendor('matomo', 'statistics', ['matomo.example.com']));
        });

        $vendors = $this->makeSut()->create();

        $this->assertNull($vendors->loading('https://www.youtube.com/embed/a'));
        $this->assertSame('matomo', $vendors->loading('https://matomo.example.com/matomo.js')?->id());
    }

    /**
     * @return list<string>
     */
    private function ids(): array
    {
        return \array_values(\array_map(
            static fn(Vendor $vendor): string => $vendor->id(),
            \iterator_to_array($this->makeSut()->create())
        ));
    }

    private function makeSut(): VendorsFactory
    {
        return new VendorsFactory(
            new VendorPresets(),
            new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState()),
            new Options()
        );
    }
}
