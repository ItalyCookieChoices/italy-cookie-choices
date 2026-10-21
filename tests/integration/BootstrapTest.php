<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;

final class BootstrapTest extends WPTestCase
{
    private const PLUGIN_DIR = WP_PLUGIN_DIR . '/italy-cookie-choices/legacy';

    public function testConstantsAreDefined(): void
    {
        $this->assertTrue(ITALY_COOKIE_CHOICES_PLUGIN);
        $this->assertSame(self::PLUGIN_DIR . '/define-constants.php', ITALY_COOKIE_CHOICES_FILE);
        $this->assertSame(self::PLUGIN_DIR . '/', ITALY_COOKIE_CHOICES_PLUGIN_PATH);
        $this->assertSame('italy-cookie-choices/legacy/define-constants.php', ITALY_COOKIE_CHOICES_BASENAME);
        $this->assertSame(self::PLUGIN_DIR, ITALY_COOKIE_CHOICES_DIRNAME);
    }

    public function testPluginLoadedActionIsFired(): void
    {
        $this->assertSame(1, \did_action('icc_plugin_loaded'));
    }

    public function testTextDomainIsLoadedOnInit(): void
    {
        $this->assertSame(100, $this->findClosurePriority('init'));
    }

    public function testInitFilesAreRequiredOnPluginsLoaded(): void
    {
        $this->assertSame(11, $this->findClosurePriority('plugins_loaded'));
    }

    public function testAdminSettingsAreNotLoadedOnTheFrontEnd(): void
    {
        global $wp_filter;

        foreach ($wp_filter['admin_menu']->callbacks ?? [] as $callbacks) {
            foreach ($callbacks as $callback) {
                $this->assertFalse(
                    \is_array($callback['function'])
                    && $callback['function'][0] instanceof \Italy_Cookie_Choices\Admin\Settings
                );
            }
        }
    }

    /**
     * @return int|false
     */
    private function findClosurePriority(string $hook)
    {
        global $wp_filter;

        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (!$callback['function'] instanceof \Closure) {
                    continue;
                }

                $reflection = new \ReflectionFunction($callback['function']);
                if ($reflection->getFileName() === self::PLUGIN_DIR . '/bootstrap.php') {
                    return $priority;
                }
            }
        }

        return false;
    }
}
