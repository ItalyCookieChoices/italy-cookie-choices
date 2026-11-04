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

    public function testTheGoogleTagGatewayPathWaitsForTheMarketingConsent(): void
    {
        \update_option(Schema::OPTION, ['google_tag_gateway_path' => '/metrics']);
        $host = (string) \wp_parse_url(\home_url(), \PHP_URL_HOST);

        $vendors = $this->makeSut()->create();

        $this->assertSame('google-tag-gateway', $vendors->loading('https://' . $host . '/metrics/tag.js')?->id());
        $relative = $vendors->loading('/metrics/tag.js');
        $this->assertNotNull($relative);
        $this->assertSame('google-tag-gateway', $relative->id());
        $this->assertSame('marketing', $relative->category());
        $this->assertNull($vendors->loading('https://' . $host . '/blog/metrics/tag.js'));
        $this->assertNull($vendors->loading('https://other.example/metrics/tag.js'));
    }

    public function testTheIdsOfTheGoogleTagsWinOverTheGatewayPath(): void
    {
        \update_option(Schema::OPTION, ['google_tag_gateway_path' => '/metrics']);

        $this->assertSame('google-analytics', $this->makeSut()->create()->loading('/metrics/?id=G-1')?->id());
    }

    public function testWithoutTheGatewayPathTheSitePathsAreNotBlocked(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $this->assertNull($this->makeSut()->create()->loading('/metrics/tag.js'));
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
