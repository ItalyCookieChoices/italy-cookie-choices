<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Settings;

use ItalyCookieChoices\Settings\NetworkSettings;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class NetworkSettingsTest extends WPTestCase
{
    public function testEverySiteHasItsOwnSettingsByDefault(): void
    {
        $sut = new NetworkSettings();
        $siteId = $this->site();

        $this->assertFalse($sut->sharesTheMainSite());
        \switch_to_blog($siteId);
        $this->assertFalse($sut->usesTheMainSite());
        \restore_current_blog();
    }

    public function testTheOtherSitesUseTheSettingsOfTheMainSite(): void
    {
        $sut = new NetworkSettings();
        $siteId = $this->site();

        $sut->share(true);

        $this->assertTrue($sut->sharesTheMainSite());
        $this->assertSame(['shared' => '1'], \get_site_option(NetworkSettings::OPTION));
        $this->assertFalse($sut->usesTheMainSite());
        \switch_to_blog($siteId);
        $this->assertTrue($sut->usesTheMainSite());
        \restore_current_blog();

        $sut->share(false);

        $this->assertFalse($sut->sharesTheMainSite());
        $this->assertSame([], \get_site_option(NetworkSettings::OPTION));
    }

    private function site(): int
    {
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);

        return $siteId;
    }
}
