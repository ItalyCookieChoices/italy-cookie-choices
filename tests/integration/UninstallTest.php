<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;

final class UninstallTest extends WPTestCase
{
    public function testUninstallDeletesTheOptionsTheLogAndItsCleanup(): void
    {
        \update_option('italy_cookie_choices', ['active' => '1']);
        \update_option('italy_cookie_choices_version', 1);
        \wp_schedule_event(\time(), 'daily', 'italy_cookie_choices_consent_log_cleanup');
        $queries = [];
        // Before the test case turns the DROP TABLE into a DROP TEMPORARY TABLE.
        \add_filter('query', static function (string $query) use (&$queries): string {
            $queries[] = $query;

            return $query;
        }, 1);
        if (!\defined('WP_UNINSTALL_PLUGIN')) {
            \define('WP_UNINSTALL_PLUGIN', 'italy-cookie-choices/italy-cookie-choices.php');
        }

        require \dirname(ITALY_COOKIE_CHOICES_DIRNAME) . '/uninstall.php';

        global $wpdb;

        /** @var \wpdb $wpdb */
        $this->assertNull(\get_option('italy_cookie_choices', null));
        $this->assertNull(\get_option('italy_cookie_choices_version', null));
        $this->assertNull(\get_option('italy_cookie_choices_log_table_version', null));
        $this->assertFalse(\wp_next_scheduled('italy_cookie_choices_consent_log_cleanup'));
        $this->assertContains('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'icc_consent_log', $queries);
    }

    public function testUninstallUnregistersTheWpmlStrings(): void
    {
        require_once \codecept_root_dir('tests/_support/stubs/multilingual.php');
        $GLOBALS['icc_wpml_unregistered'] = [];
        if (!\defined('WP_UNINSTALL_PLUGIN')) {
            \define('WP_UNINSTALL_PLUGIN', 'italy-cookie-choices/italy-cookie-choices.php');
        }

        require \dirname(ITALY_COOKIE_CHOICES_DIRNAME) . '/uninstall.php';

        $unregistered = $GLOBALS['icc_wpml_unregistered'];
        $this->assertIsArray($unregistered);
        $this->assertContains(['Italy Cookie Choices', 'Banner text'], $unregistered);
        $this->assertContains(['Italy Cookie Choices', 'Category marketing description'], $unregistered);
        $this->assertCount(15, $unregistered);
        unset($GLOBALS['icc_wpml_unregistered']);
    }
}
