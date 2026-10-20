<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Admin;

use Italy_Cookie_Choices\Admin\Settings;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SettingsTest extends WPTestCase
{
    private const OPTION_NAME = 'italy_cookie_choices';

    private const GROUP = 'italy_cl_options_group';

    private array $get = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->get = $_GET;
        \delete_option(self::OPTION_NAME);
        $this->resetAssets();
    }

    public function tearDown(): void
    {
        $_GET = $this->get;
        $this->resetAssets();
        parent::tearDown();
    }

    public function testItRegistersTheAdminHooks(): void
    {
        $sut = new Settings();

        $this->assertSame(10, \has_action('admin_menu', [$sut, 'addMenuPage']));
        $this->assertSame(10, \has_action('admin_init', [$sut, 'italy_cl_settings_init']));
        $this->assertSame(10, \has_action('admin_init', [$sut, 'settings_export']));
        $this->assertSame(10, \has_action('admin_init', [$sut, 'settings_import']));
        $this->assertSame(10, \has_filter('plugin_action_links_' . ITALY_COOKIE_CHOICES_BASENAME, [$sut, 'plugin_action_links']));
        $this->assertFalse(\has_action('admin_enqueue_scripts', [$sut, 'add_script_and_style']));
    }

    public function testItLoadsTheAssetsOnlyOnItsOwnPage(): void
    {
        $_GET['page'] = 'italy-cookie-choices';

        $sut = new Settings();

        $this->assertSame(10, \has_action('admin_enqueue_scripts', [$sut, 'add_script_and_style']));
    }

    public function testItDoesNotLoadTheAssetsOnOtherPages(): void
    {
        $_GET['page'] = 'other-page';

        $sut = new Settings();

        $this->assertFalse(\has_action('admin_enqueue_scripts', [$sut, 'add_script_and_style']));
    }

    public function testPluginActionLinksArePrepended(): void
    {
        $links = (new Settings())->plugin_action_links(['deactivate' => '<a href="#">Deactivate</a>']);

        $this->assertSame(
            [
                '<a href="https://github.com/ItalyCookieChoices/italy-cookie-choices/wiki" target="_blank">Documentation</a>',
                '<a href="options-general.php?page=italy-cookie-choices">Settings</a>',
                'deactivate' => '<a href="#">Deactivate</a>',
            ],
            $links
        );
    }

    public function testItAddsTheOptionsPageForAdministrators(): void
    {
        \wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

        $sut = new Settings();

        $sut->addMenuPage();

        $this->assertContains(
            ['Italy Cookie Choices', 'manage_options', 'italy-cookie-choices', 'Italy Cookie Choices Dashboard'],
            $GLOBALS['submenu']['options-general.php']
        );
        $this->assertSame(10, \has_action('admin_page_italy-cookie-choices', [$sut, 'dashboard']));
    }

    public function testItDoesNotAddTheOptionsPageForEditors(): void
    {
        \wp_set_current_user(self::factory()->user->create(['role' => 'editor']));

        (new Settings())->addMenuPage();

        $this->assertFalse(\has_action('admin_page_italy-cookie-choices'));
    }

    public function testSettingsInitCreatesTheDefaultOptions(): void
    {
        (new Settings())->italy_cl_settings_init();

        $this->assertSame(
            [
                'text' => '',
                'url' => '',
                'anchor_text' => '',
                'button_text' => '',
                'cookie_name' => 'displayCookieConsent',
                'cookie_value' => 'y',
                'content_message_text' => '',
                'content_message_button_text' => '',
            ],
            \get_option(self::OPTION_NAME)
        );
    }

    public function testSettingsInitKeepsTheExistingOptions(): void
    {
        \update_option(self::OPTION_NAME, ['active' => '1']);

        (new Settings())->italy_cl_settings_init();

        $this->assertSame(['active' => '1'], \get_option(self::OPTION_NAME));
    }

    public function testSettingsInitRegistersTheSetting(): void
    {
        $sut = new Settings();

        $sut->italy_cl_settings_init();

        $this->assertArrayHasKey(self::OPTION_NAME, \get_registered_settings());
        $this->assertSame(self::GROUP, \get_registered_settings()[self::OPTION_NAME]['group']);
        $this->assertSame(10, \has_filter('sanitize_option_' . self::OPTION_NAME, [$sut, 'sanitize_callback']));
    }

    public function testSettingsInitRegistersTheSections(): void
    {
        global $wp_settings_sections;
        $sut = new Settings();

        $sut->italy_cl_settings_init();

        $this->assertSame(
            ['setting_section', 'style_setting_section', 'advanced_setting_section'],
            \array_keys($wp_settings_sections[self::GROUP])
        );
        $this->assertSame(
            [$sut, 'italy_cl_settings_section_callback'],
            $wp_settings_sections[self::GROUP]['setting_section']['callback']
        );
        $this->assertSame(
            [$sut, 'italy_cl_style_settings_section_callback'],
            $wp_settings_sections[self::GROUP]['style_setting_section']['callback']
        );
        $this->assertSame(
            [$sut, 'italy_cl_advanced_settings_section_callback'],
            $wp_settings_sections[self::GROUP]['advanced_setting_section']['callback']
        );
    }

    public function testSettingsInitRegistersTheFields(): void
    {
        global $wp_settings_fields;
        $sut = new Settings();

        $sut->italy_cl_settings_init();

        $this->assertSame(
            ['active', 'banner', 'scroll', 'secondView', 'reload', 'text', 'url', 'slug', 'anchor_text', 'button_text'],
            \array_keys($wp_settings_fields[self::GROUP]['setting_section'])
        );
        $this->assertSame(
            ['js_template', 'html_margin', 'banner_bg', 'banner_text_color', 'customCSS', 'customClass'],
            \array_keys($wp_settings_fields[self::GROUP]['style_setting_section'])
        );
        $this->assertSame(
            [
                'cookie_name',
                'cookie_value',
                'target',
                'block',
                'custom_script_block_body_exclude',
                'custom_script_block',
                'content_message_text',
                'content_message_button_text',
            ],
            \array_keys($wp_settings_fields[self::GROUP]['advanced_setting_section'])
        );

        foreach ($wp_settings_fields[self::GROUP] as $fields) {
            foreach ($fields as $id => $field) {
                $this->assertSame([$sut, 'italy_cl_option_' . $id], $field['callback']);
            }
        }
    }

    public function testItEnqueuesTheAssetsOnItsSettingsPage(): void
    {
        (new Settings())->add_script_and_style('settings_page_italy-cookie-choices');

        $this->assertTrue(\wp_style_is('wp-color-picker'));
        $this->assertTrue(\wp_script_is('jquery-ui-autocomplete'));
        $this->assertTrue(\wp_style_is('italy-cookie-choices-css'));
        $this->assertTrue(\wp_script_is('italy-cookie-choices-script'));
        $this->assertSame(
            \plugins_url('admin/css/admin.css', ITALY_COOKIE_CHOICES_FILE),
            \wp_styles()->registered['italy-cookie-choices-css']->src
        );
        $this->assertSame(['dashicons'], \wp_styles()->registered['italy-cookie-choices-css']->deps);
        $this->assertSame(
            \plugins_url('admin/js/src/script.js', ITALY_COOKIE_CHOICES_FILE),
            \wp_scripts()->registered['italy-cookie-choices-script']->src
        );
        $this->assertSame(
            ['wp-color-picker', 'jquery-ui-widget', 'jquery-ui-autocomplete', 'jquery-effects-shake'],
            \wp_scripts()->registered['italy-cookie-choices-script']->deps
        );
        $this->assertSame(1, \wp_scripts()->get_data('italy-cookie-choices-script', 'group'));
    }

    public function testItDoesNotEnqueueTheAssetsOnOtherPages(): void
    {
        (new Settings())->add_script_and_style('index.php');

        $this->assertFalse(\wp_style_is('italy-cookie-choices-css'));
        $this->assertFalse(\wp_script_is('italy-cookie-choices-script'));
    }

    public function testTheDashboardRendersTheForms(): void
    {
        \wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        \update_option(self::OPTION_NAME, ['text' => '', 'url' => '', 'anchor_text' => '', 'button_text' => '']);
        $sut = new Settings();
        $sut->italy_cl_settings_init();

        \ob_start();
        $sut->dashboard();
        $output = (string) \ob_get_clean();

        $this->assertStringContainsString("<form action='options.php' method='post' id='italy-cookie-choices-ID'>", $output);
        $this->assertStringContainsString("name='option_page' value='" . self::GROUP . "'", $output);
        $this->assertStringContainsString('<input type="hidden" name="icc_action" value="export_settings" />', $output);
        $this->assertStringContainsString('<input type="hidden" name="icc_action" value="import_settings" />', $output);
        $this->assertStringContainsString('name="icc_export_nonce"', $output);
        $this->assertStringContainsString('name="icc_import_nonce"', $output);
        $this->assertStringContainsString('<input type="file" name="icc_import_file"/>', $output);
    }

    public function testTheDashboardIsForbiddenWithoutCapability(): void
    {
        \wp_set_current_user(self::factory()->user->create(['role' => 'editor']));

        $this->expectException(\WPDieException::class);
        $this->expectExceptionMessage('You do not have sufficient permissions to access this page.');

        (new Settings())->dashboard();
    }

    public function sectionCallbacks(): iterable
    {
        yield 'main' => ['italy_cl_settings_section_callback', 'Customize your banner for cookie law'];
        yield 'style' => ['italy_cl_style_settings_section_callback', 'Customize your style settings'];
        yield 'advanced' => ['italy_cl_advanced_settings_section_callback', 'Customize your advanced settings'];
    }

    /**
     * @dataProvider sectionCallbacks
     */
    public function testTheSectionCallbacksPrintTheirDescriptions(string $callback, string $description): void
    {
        $sut = new Settings();

        \ob_start();
        $sut->{$callback}();
        $this->assertSame($description, (string) \ob_get_clean());
    }

    /**
     * Current behaviour: on the very first load the option does not exist yet,
     * the fields read it from memory and raise a notice.
     */
    public function testTheFirstRenderBeforeTheOptionExistsRaisesANotice(): void
    {
        $sut = new Settings();
        $sut->italy_cl_settings_init();

        $notices = [];
        \set_error_handler(static function (int $level, string $message) use (&$notices): bool {
            $notices[] = $message;
            return true;
        }, E_NOTICE);

        \ob_start();
        try {
            $sut->italy_cl_option_anchor_text([]);
        } finally {
            \ob_end_clean();
            \restore_error_handler();
        }

        $this->assertSame(['Trying to access array offset on value of type bool'], $notices);
    }

    public function testWpJsonEncodeEncodesData(): void
    {
        $sut = new Settings();

        $this->assertSame('["a\/b"]', $sut->wp_json_encode(['a/b']));
        $this->assertSame("[\n    1\n]", $sut->wp_json_encode([1], JSON_PRETTY_PRINT));
        $this->assertFalse($sut->wp_json_encode([[1]], 0, 1));
    }

    private function resetAssets(): void
    {
        $GLOBALS['wp_scripts'] = null;
        $GLOBALS['wp_styles'] = null;
    }
}
