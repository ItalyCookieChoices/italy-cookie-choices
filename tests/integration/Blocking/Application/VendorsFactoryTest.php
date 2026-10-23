<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\Application;

use ItalyCookieChoices\Blocking\Application\BuildingVendors;
use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class VendorsFactoryTest extends WPTestCase
{
    use BuildsOptions;

    public function testThePresetsAreBlockedByDefault(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $this->assertSame(
            \array_map(static fn(Vendor $vendor): string => $vendor->id(), (new VendorPresets())->all()),
            $this->ids()
        );
    }

    public function testThePresetsTurnedOffAreNotBlocked(): void
    {
        \update_option(Schema::OPTION, ['disabled_vendors' => ['youtube', 'hotjar']]);

        $ids = $this->ids();

        $this->assertNotContains('youtube', $ids);
        $this->assertNotContains('hotjar', $ids);
        $this->assertContains('vimeo', $ids);
    }

    public function testTheCustomVendorsBlockTheirPatternsInTheirCategory(): void
    {
        \update_option(Schema::OPTION, [
            'custom_vendors' => [
                [
                    'id' => 'tracker',
                    'name' => 'Tracker',
                    'category' => 'statistics',
                    'patterns' => ['tracker.example.com', 'widgets.example.com'],
                ],
            ],
        ]);

        $vendors = $this->makeSut()->create();

        $custom = $vendors->loading('https://widgets.example.com/frame');
        $this->assertNotNull($custom);
        $this->assertSame('tracker', $custom->id());
        $this->assertSame('statistics', $custom->category());
        $this->assertSame('tracker', $vendors->running('load("tracker.example.com")')?->id());
    }

    public function testAnInvalidCustomVendorIsSkipped(): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(Schema::OPTION, [
            'custom_vendors' => [
                ['id' => 'Not Valid', 'category' => 'marketing', 'patterns' => ['invalid.example.com']],
                ['id' => 'no-category', 'patterns' => ['category.example.com']],
                'not a vendor',
                ['id' => 'valid', 'category' => 'marketing', 'patterns' => ['valid.example.com', 42]],
            ],
        ]);

        $vendors = $this->makeSut()->create();

        $this->assertNull($vendors->loading('https://invalid.example.com/'));
        $this->assertNull($vendors->loading('https://category.example.com/'));
        $this->assertSame('valid', $vendors->loading('https://valid.example.com/')?->id());
    }

    public function testTheAllowedPatternsAreNeverBlocked(): void
    {
        \update_option(Schema::OPTION, [
            'allowed_patterns' => ['youtube.com/embed/public', 'iccAllowed'],
            'custom_vendors' => [
                ['id' => 'icc', 'name' => 'ICC', 'category' => 'marketing', 'patterns' => ['icc']],
            ],
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
            $this->options()
        );
    }
}
