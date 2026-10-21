<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;

final class RequirementsTest extends WPTestCase
{
    private const REQUIREMENTS = WP_PLUGIN_DIR . '/italy-cookie-choices/requirements.php';

    public function testTheRequirementsAreMetOnTheSupportedPlatform(): void
    {
        \remove_all_actions('admin_notices');

        $this->assertTrue(require self::REQUIREMENTS);
        $this->assertFalse(\has_action('admin_notices'));
    }

    public function testTheNoticeExplainsTheMissingRequirements(): void
    {
        global $wp_version;
        $currentVersion = $wp_version;
        $wp_version = '6.0';
        \remove_all_actions('admin_notices');

        try {
            $met = require self::REQUIREMENTS;
        } finally {
            $wp_version = $currentVersion;
        }

        \ob_start();
        \do_action('admin_notices');
        $notice = (string) \ob_get_clean();

        $this->assertFalse($met);
        $this->assertSame(
            '<div class="notice notice-error"><p>Italy Cookie Choices is not running: it needs PHP 8.2 and'
            . ' WordPress 6.1 or newer, this site has PHP ' . PHP_VERSION . ' and WordPress 6.0.</p></div>',
            $notice
        );
    }
}
