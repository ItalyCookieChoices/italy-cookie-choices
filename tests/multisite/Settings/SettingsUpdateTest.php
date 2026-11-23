<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Settings;

use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Settings\SettingsUpdate;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SettingsUpdateTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        (new NetworkSettings())->share(true);
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);
        \switch_to_blog($siteId);
    }

    public function tearDown(): void
    {
        \delete_site_option(NetworkSettings::OPTION);
        \restore_current_blog();
        parent::tearDown();
    }

    public function testASiteThatUsesTheMainSiteChangesItsTexts(): void
    {
        $saved = $this->service(SettingsUpdate::class)->apply(['text' => 'Ciao']);

        $this->assertSame('Ciao', $saved['text'] ?? null);
    }

    public function testASiteThatUsesTheMainSiteDoesNotChangeTheOtherSettings(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/only its texts change, not: reload\./');

        $this->service(SettingsUpdate::class)->apply(['text' => 'Ciao', 'reload' => '1']);
    }
}
