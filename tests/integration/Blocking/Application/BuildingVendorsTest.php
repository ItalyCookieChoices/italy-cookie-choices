<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\Application;

use ItalyCookieChoices\Blocking\Application\BuildingVendors;
use ItalyCookieChoices\Blocking\Domain\Vendor;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class BuildingVendorsTest extends WPTestCase
{
    public function testItAddsTheVendorsAtTheEnd(): void
    {
        $event = new BuildingVendors([new Vendor('youtube', 'marketing', ['youtube.com'])]);

        $event->addVendor(new Vendor('matomo', 'statistics', ['matomo.example.com']));

        $this->assertSame(['youtube', 'matomo'], $this->ids($event));
    }

    public function testItReplacesAVendorInItsPlace(): void
    {
        $event = new BuildingVendors([
            new Vendor('youtube', 'marketing', ['youtube.com']),
            new Vendor('vimeo', 'marketing', ['player.vimeo.com']),
        ]);

        $event->addVendor(new Vendor('youtube', 'statistics', ['youtube.com']));

        $this->assertSame(['youtube', 'vimeo'], $this->ids($event));
        $this->assertSame('statistics', $event->vendors()->loading('https://youtube.com/embed/a')?->category());
    }

    public function testItRemovesAVendor(): void
    {
        $event = new BuildingVendors([
            new Vendor('youtube', 'marketing', ['youtube.com']),
            new Vendor('vimeo', 'marketing', ['player.vimeo.com']),
        ]);

        $event->removeVendor('youtube');
        $event->removeVendor('missing');

        $this->assertSame(['vimeo'], $this->ids($event));
    }

    public function testItAllowsTexts(): void
    {
        $event = new BuildingVendors([new Vendor('youtube', 'marketing', ['youtube.com'])], ['youtube.com/embed/a']);

        $event->allow('youtube.com/embed/b');

        $vendors = $event->vendors();
        $this->assertNull($vendors->loading('https://youtube.com/embed/a'));
        $this->assertNull($vendors->loading('https://youtube.com/embed/b'));
        $this->assertNotNull($vendors->loading('https://youtube.com/embed/c'));
    }

    /**
     * @return list<string>
     */
    private function ids(BuildingVendors $event): array
    {
        return \array_values(\array_map(
            static fn(Vendor $vendor): string => $vendor->id(),
            \iterator_to_array($event->vendors())
        ));
    }
}
