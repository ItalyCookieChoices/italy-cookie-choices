<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Settings\UI;

use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Settings\UI\NetworkSettingsPage;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyStrap\Asset\AssetLoader;
use ItalyStrap\View\ViewInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class NetworkSettingsPageTest extends WPTestCase
{
    use BuildsContainer;

    private const BUILD_URL = 'https://example.com/wp-content/plugins/italy-cookie-choices/assets/build/';
    private const LANGUAGES = '/plugin/languages';
    private const HOOK = 'settings_page_italy-cookie-choices';

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        $GLOBALS['wp_styles'] = new \WP_Styles();
    }

    public function tearDown(): void
    {
        \delete_site_option(NetworkSettings::OPTION);
        unset($GLOBALS['wp_scripts'], $GLOBALS['wp_styles']);
        \set_current_screen('front');
        parent::tearDown();
    }

    public function testItAddsThePageAndItsAssetsToTheNetworkSettings(): void
    {
        $this->assertSame(
            [
                'network_admin_menu' => 'addPage',
                'admin_enqueue_scripts' => 'enqueue',
            ],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );

        $this->logInAsSuperAdmin();
        $this->makeSut()->addPage();

        global $submenu;
        $this->assertIsArray($submenu);
        $settings = $submenu['settings.php'] ?? [];
        $this->assertIsArray($settings);
        $this->assertContains(NetworkSettingsPage::SLUG, \array_column($settings, 2));
    }

    public function testItPrintsTheRootOfTheApp(): void
    {
        \ob_start();
        $this->makeSut()->render();
        $html = (string) \ob_get_clean();

        $this->assertStringContainsString('<h1>Cookie Choices</h1>', $html);
        $this->assertStringContainsString('<div id="icc-network">', $html);
    }

    public function testItEnqueuesTheAppOnlyOnItsPageOfTheNetworkAdmin(): void
    {
        $sut = $this->makeSut();

        \set_current_screen('dashboard-network');
        $sut->enqueue('index.php');
        $this->assertFalse(\wp_script_is(NetworkSettingsPage::HANDLE, 'registered'));

        \set_current_screen('dashboard');
        $sut->enqueue(self::HOOK);
        $this->assertFalse(\wp_script_is(NetworkSettingsPage::HANDLE, 'registered'));

        \set_current_screen('dashboard-network');
        $sut->enqueue(self::HOOK);
        $script = \wp_scripts()->registered[NetworkSettingsPage::HANDLE];
        $this->assertSame(self::BUILD_URL . 'network-settings.js', $script->src);
        $this->assertSame(['wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n'], $script->deps);
        $this->assertSame('italy-cookie-choices', $script->textdomain);
        $this->assertSame(self::LANGUAGES, $script->translations_path);
        $this->assertSame(
            ['window.italyCookieChoicesNetwork = ' . \wp_json_encode($sut->data()) . ';'],
            \array_values(\array_filter((array) \wp_scripts()->get_data(NetworkSettingsPage::HANDLE, 'before')))
        );
        $this->assertTrue(\wp_style_is(NetworkSettingsPage::HANDLE, 'enqueued'));
        $style = \wp_styles()->registered[NetworkSettingsPage::HANDLE];
        $this->assertSame(self::BUILD_URL . 'network-settings.css', $style->src);
        $this->assertSame(['wp-components'], $style->deps);
    }

    public function testNothingIsEnqueuedWhenTheAssetsAreNotBuilt(): void
    {
        \set_current_screen('dashboard-network');
        $this->makeSut('/not/built/')->enqueue(self::HOOK);

        $this->assertFalse(\wp_script_is(NetworkSettingsPage::HANDLE, 'registered'));
    }

    public function testThePageGetsTheSwitchAndTheSites(): void
    {
        (new NetworkSettings())->share(true);
        $siteId = self::factory()->blog->create(['title' => 'Italiano', 'path' => '/it/']);
        $this->assertIsInt($siteId);

        $data = $this->makeSut()->data();

        $this->assertTrue($data['shared']);
        $this->assertSame(\get_main_site_id(), $data['mainSiteId']);
        $this->assertContains(
            ['id' => $siteId, 'name' => 'Italiano', 'url' => \get_home_url($siteId, '/')],
            $data['sites']
        );
        $this->assertSame(\get_main_site_id(), $data['sites'][0]['id'] ?? null);
    }

    private function makeSut(string $buildPath = ''): NetworkSettingsPage
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $this->assertIsString($fixtures);

        return new NetworkSettingsPage(
            new NetworkSettings(),
            $this->service(ViewInterface::class),
            new AssetLoader(),
            $buildPath === '' ? $fixtures : $buildPath,
            self::BUILD_URL,
            self::LANGUAGES
        );
    }

    private function logInAsSuperAdmin(): void
    {
        $userId = self::factory()->user->create(['role' => 'administrator']);
        $this->assertIsInt($userId);
        \grant_super_admin($userId);
        \wp_set_current_user($userId);
    }
}
