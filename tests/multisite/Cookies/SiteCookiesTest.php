<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Cookies;

use ItalyCookieChoices\Cookies\Application\SiteCookies;
use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SiteCookiesTest extends WPTestCase
{
    use BuildsContainer;

    public function tearDown(): void
    {
        \delete_site_option(NetworkSettings::OPTION);
        \restore_current_blog();
        parent::tearDown();
    }

    public function testTheCookiesOfASiteThatUsesTheMainSiteDoNotChange(): void
    {
        (new NetworkSettings())->share(true);
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);
        \switch_to_blog($siteId);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The cookies of this site come from the main site of the network.');

        $this->service(SiteCookies::class)->add('wp_lang', 'necessary', 'Session', 'Language');
    }
}
