<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Settings\UI;

use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SettingsPageTest extends WPTestCase
{
    use BuildsContainer;

    public function tearDown(): void
    {
        \delete_site_option(NetworkSettings::OPTION);
        parent::tearDown();
    }

    public function testThePageOfASiteThatUsesTheMainSiteEditsOnlyTheTexts(): void
    {
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);
        $sut = $this->service(SettingsPage::class);

        \switch_to_blog($siteId);
        $this->assertFalse($sut->data()['textsOnly']);
        (new NetworkSettings())->share(true);
        $this->assertTrue($sut->data()['textsOnly']);
        \restore_current_blog();

        $this->assertFalse($sut->data()['textsOnly']);
    }
}
