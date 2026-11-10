<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite;

use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Settings\Schema;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class UninstallTest extends WPTestCase
{
    public function testUninstallDeletesTheNetworkSettingAndTheOptionsOfEverySite(): void
    {
        (new NetworkSettings())->share(true);
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);
        \update_blog_option($siteId, Schema::OPTION, ['active' => '1']);
        if (!\defined('WP_UNINSTALL_PLUGIN')) {
            \define('WP_UNINSTALL_PLUGIN', 'italy-cookie-choices/italy-cookie-choices.php');
        }

        require \dirname(ITALY_COOKIE_CHOICES_DIRNAME) . '/uninstall.php';

        $this->assertNull(\get_site_option(NetworkSettings::OPTION, null));
        $this->assertNull(\get_blog_option($siteId, Schema::OPTION, null));
    }
}
