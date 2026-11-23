<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings\UI;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Consent\UI\ConsentTexts;
use ItalyCookieChoices\Cookies\Application\PresetCookies;
use ItalyCookieChoices\ConsentLog\UI\ConsentLogExport;
use ItalyCookieChoices\Migration\Application\MigrationSources;
use ItalyCookieChoices\Multilingual\Application\LinkTranslations;
use ItalyCookieChoices\Multilingual\UI\LanguagesData;
use ItalyCookieChoices\Scan\UI\ScheduleData;
use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyStrap\Asset\AssetLoader;
use ItalyStrap\View\ViewInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SettingsPageTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;

    private const BUILD_URL = 'https://example.com/wp-content/plugins/italy-cookie-choices/assets/build/';
    private const LANGUAGES = '/plugin/languages';

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        $GLOBALS['wp_styles'] = new \WP_Styles();
    }

    public function tearDown(): void
    {
        unset($GLOBALS['wp_scripts'], $GLOBALS['wp_styles']);
        parent::tearDown();
    }

    public function testItAddsThePageAndItsAssets(): void
    {
        $this->assertSame(
            ['admin_menu' => 'addPage', 'admin_init' => 'redirectTheOldUrl', 'admin_enqueue_scripts' => 'enqueue'],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testThePageIsATopLevelMenuForTheAdministrators(): void
    {
        $this->setCurrentUser('administrator');

        $this->makeSut()->addPage();

        /** @var array<int|string, array<int, string>> $menu */
        global $menu;
        $this->assertSame(
            ['Cookie Choices', 'manage_options', 'italy-cookie-choices', 'Cookie Choices'],
            \array_slice($menu[81] ?? [], 0, 4)
        );
        $icon = (string) \file_get_contents(\dirname(__DIR__, 4) . '/assets/images/menu-icon.svg');
        $this->assertSame('data:image/svg+xml;base64,' . \base64_encode($icon), $menu[81][6] ?? null);
        $this->assertSame(\admin_url('admin.php?page=italy-cookie-choices'), $this->makeSut()->url());
    }

    public function testTheMenuHasTheDashiconWithoutTheIconFile(): void
    {
        $this->setCurrentUser('administrator');
        // The menu of the test before.
        $GLOBALS['menu'] = [];

        $this->makeSut('', '/not/found/menu-icon.svg')->addPage();

        /** @var array<int|string, array<int, string>> $menu */
        global $menu;
        $this->assertSame('dashicons-privacy', $menu[81][6] ?? null);
    }

    public function testTheOldUrlUnderTheSettingsIsRecognized(): void
    {
        $GLOBALS['pagenow'] = 'options-general.php';
        $GLOBALS['plugin_page'] = 'italy-cookie-choices';
        $this->assertTrue($this->makeSut()->isTheOldUrl());

        $GLOBALS['pagenow'] = 'admin.php';
        $this->assertFalse($this->makeSut()->isTheOldUrl());

        $GLOBALS['pagenow'] = 'options-general.php';
        $GLOBALS['plugin_page'] = 'other-plugin';
        $this->assertFalse($this->makeSut()->isTheOldUrl());
        unset($GLOBALS['plugin_page']);
    }

    public function testItPrintsTheRootOfTheApp(): void
    {
        $GLOBALS['title'] = 'Cookie Choices';

        \ob_start();
        $this->makeSut()->render();
        $html = (string) \ob_get_clean();

        $this->assertStringContainsString('<h1>Cookie Choices</h1>', $html);
        $this->assertStringContainsString('<div id="icc-admin">', $html);
        unset($GLOBALS['title']);
    }

    public function testItEnqueuesTheAppOnlyOnItsPage(): void
    {
        $sut = $this->makeSut();

        $sut->enqueue('index.php');
        $this->assertFalse(\wp_script_is(SettingsPage::HANDLE, 'registered'));

        $sut->enqueue('toplevel_page_italy-cookie-choices');
        $script = \wp_scripts()->registered[SettingsPage::HANDLE];
        $this->assertSame(self::BUILD_URL . 'settings.js', $script->src);
        $this->assertSame(['wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n'], $script->deps);
        $this->assertSame('italy-cookie-choices', $script->textdomain);
        $this->assertSame(self::LANGUAGES, $script->translations_path);
        $this->assertSame(
            ['window.italyCookieChoicesAdmin = ' . \wp_json_encode($sut->data()) . ';'],
            \array_values(\array_filter((array) \wp_scripts()->get_data(SettingsPage::HANDLE, 'before')))
        );
        $this->assertTrue(\wp_style_is(SettingsPage::HANDLE, 'enqueued'));
        $this->assertSame(['wp-components'], \wp_styles()->registered[SettingsPage::HANDLE]->deps);
    }

    public function testThePageLoadsTheMediaLibraryForTheLogo(): void
    {
        $this->makeSut()->enqueue('toplevel_page_italy-cookie-choices');

        $this->assertSame(1, \did_action('wp_enqueue_media'));
    }

    public function testNothingIsEnqueuedWhenTheAssetsAreNotBuilt(): void
    {
        $this->makeSut('/not/built/')->enqueue('toplevel_page_italy-cookie-choices');

        $this->assertFalse(\wp_script_is(SettingsPage::HANDLE, 'registered'));
    }

    public function testThePageGetsTheCategoriesTheVendorsAndTheLinks(): void
    {
        $data = $this->makeSut()->data();

        $this->assertSame(
            ['necessary', 'preferences', 'statistics', 'marketing'],
            \array_column($data['categories'], 'id')
        );
        $this->assertSame('Statistics', $data['categories'][2]['label'] ?? null);
        $vendors = \array_column($data['vendors'], null, 'id');
        $this->assertSame(
            [
                'id' => 'youtube',
                'name' => 'YouTube',
                'category' => 'marketing',
                'cookies' => ['YSC', '__Secure-YNID', 'PREF'],
            ],
            $vendors['youtube'] ?? null
        );
        $this->assertSame(
            [
                'id' => 'google-recaptcha',
                'name' => 'Google reCAPTCHA',
                'category' => 'marketing',
                'cookies' => ['_GRECAPTCHA'],
            ],
            $vendors['google-recaptcha'] ?? null
        );
        $this->assertSame($this->service(ConsentLogExport::class)->url(), $data['consentLogExportUrl']);
        $this->assertSame(SettingsPage::DOCUMENTATION, $data['documentationUrl']);
        $this->assertFalse($data['textsOnly']);
        $this->assertContains(['slug' => 'page', 'name' => 'Pages'], $data['postTypes']);
        $this->assertNotContains('attachment', \array_column($data['postTypes'], 'slug'));
        $this->assertSame('off', $data['scanSchedule']['frequency'] ?? null);
        $this->assertSame(['policy' => [], 'legal-notice' => []], $data['linkTranslations']);
        $this->assertSame([], $data['migrationSources']);
    }

    public function testOnlyTheUsersWithUnfilteredHtmlEditTheCustomScripts(): void
    {
        $this->logInAs('author');
        $this->assertFalse($this->makeSut()->data()['canEditScripts']);

        $this->logInAs('administrator');
        $this->assertTrue($this->makeSut()->data()['canEditScripts']);
    }

    public function testThePageGetsTheDefaultLabelsNotTheSavedOnes(): void
    {
        \update_option('italy_cookie_choices', ['categories' => ['statistics' => ['label' => 'Analytics']]]);

        $this->assertSame('Statistics', $this->makeSut()->data()['categories'][2]['label'] ?? null);
    }

    private function makeSut(string $buildPath = '', string $menuIcon = ''): SettingsPage
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $this->assertIsString($fixtures);

        return new SettingsPage(
            $this->service(ConsentTexts::class),
            $this->service(VendorPresets::class),
            $this->service(PresetCookies::class),
            $this->service(ConsentLogExport::class),
            new NetworkSettings(),
            $this->service(LanguagesData::class),
            $this->service(ScheduleData::class),
            $this->service(LinkTranslations::class),
            $this->service(MigrationSources::class),
            $this->service(ViewInterface::class),
            new AssetLoader(),
            $buildPath === '' ? $fixtures : $buildPath,
            self::BUILD_URL,
            self::LANGUAGES,
            $menuIcon === '' ? \dirname(__DIR__, 4) . '/assets/images/menu-icon.svg' : $menuIcon
        );
    }

    private function setCurrentUser(string $role): void
    {
        $userId = self::factory()->user->create(['role' => $role]);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);
    }
}
