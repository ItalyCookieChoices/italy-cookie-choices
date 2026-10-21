<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;

final class UninstallTest extends WPTestCase
{
    public function testUninstallDeletesTheOptions(): void
    {
        \update_option('italy_cookie_choices', ['active' => '1']);
        \update_option('italy_cookie_choices_version', 1);
        if (!\defined('WP_UNINSTALL_PLUGIN')) {
            \define('WP_UNINSTALL_PLUGIN', 'italy-cookie-choices/italy-cookie-choices.php');
        }

        require \dirname(ITALY_COOKIE_CHOICES_DIRNAME) . '/uninstall.php';

        $this->assertNull(\get_option('italy_cookie_choices', null));
        $this->assertNull(\get_option('italy_cookie_choices_version', null));
    }
}
