<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Settings\Schema;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class NetworkSettingsTest extends WPTestCase
{
    public function testASingleSiteAlwaysUsesItsOwnSettings(): void
    {
        $sut = new NetworkSettings();
        $sut->share(true);
        \update_option(Schema::OPTION, ['active' => '1', 'text' => 'Own']);

        $this->assertFalse($sut->usesTheMainSite());
        $option = \get_option(Schema::OPTION);
        $this->assertIsArray($option);
        $this->assertSame('Own', $option['text'] ?? null);
    }
}
