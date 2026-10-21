<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration;

use ItalyCookieChoices\I18n\TextDomain;
use ItalyCookieChoices\Legacy\LegacyLoader;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class BootstrapTest extends WPTestCase
{
    private const PLUGIN_DIR = WP_PLUGIN_DIR . '/italy-cookie-choices/legacy';

    public function testConstantsAreDefined(): void
    {
        $this->assertSame(true, ITALY_COOKIE_CHOICES_PLUGIN);
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
        $this->assertSame(100, $this->findListenerPriority('init', TextDomain::class));
    }

    public function testInitFilesAreRequiredOnPluginsLoaded(): void
    {
        $this->assertSame(11, $this->findListenerPriority('plugins_loaded', LegacyLoader::class));
    }

    public function testAdminSettingsAreNotLoadedOnTheFrontEnd(): void
    {
        /** @var array<string, \WP_Hook> $wp_filter */
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
     * @param class-string $listenerClass
     * @return int|false
     */
    private function findListenerPriority(string $hook, string $listenerClass)
    {
        /** @var array<string, \WP_Hook> $wp_filter */
        global $wp_filter;

        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (\is_array($callback['function']) && $callback['function'][0] instanceof $listenerClass) {
                    return (int) $priority;
                }
            }
        }

        return false;
    }
}
