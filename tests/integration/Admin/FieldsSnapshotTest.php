<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Admin;

use Italy_Cookie_Choices\Admin\Settings;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Golden master of the settings fields markup, the snapshots are created on the first run.
 */
final class FieldsSnapshotTest extends WPTestCase
{
    private const SNAPSHOTS = __DIR__ . '/../../_data/snapshots/fields/';

    private const OPTIONS = [
        'active' => '1',
        'banner' => '2',
        'scroll' => '1',
        'secondView' => '1',
        'reload' => '1',
        'text' => 'Banner <strong>text</strong>',
        'url' => 'https://example.com/policy/',
        'slug' => 'policy',
        'anchor_text' => 'More',
        'button_text' => 'Accept',
        'html_margin' => '1',
        'js_template' => 'smallbutton',
        'banner_bg' => '#111111',
        'banner_text_color' => '#222222',
        'customCSS' => '#cookieChoiceInfo{top:0}',
        'bannerStyle' => 'my-banner',
        'contentStyle' => 'my-content',
        'consentText' => 'my-text',
        'infoClass' => 'my-info',
        'closeClass' => 'my-close',
        'cookie_name' => 'myConsent',
        'cookie_value' => 'ok',
        'target' => '1',
        'block' => '1',
        'widget_block' => '1',
        'allow_iframe' => ['youtube', 'vimeo'],
        'allow_script' => ['twitter', 'facebook', 'analytics'],
        'allow_embed' => ['flash'],
        'custom_script_block_body_exclude' => '<script src="allowed.js"></script>',
        'block_iframe' => ['maps'],
        'block_script' => ['googlesyndication', 'doubleclick'],
        'block_embed' => ['movie'],
        'custom_script_block' => '<script src="blocked.js"></script>',
        'content_message_text' => 'Content <em>blocked</em>',
        'content_message_button_text' => 'Allow',
    ];

    private string $cwd = '';

    public function setUp(): void
    {
        parent::setUp();
        // The templates are required with a path relative to the plugin, catch it if it changes
        $this->cwd = (string) \getcwd();
        \chdir(\sys_get_temp_dir());
    }

    public function tearDown(): void
    {
        \chdir($this->cwd);
        parent::tearDown();
    }

    public function fields(): iterable
    {
        $fields = [
            'active',
            'banner',
            'scroll',
            'secondView',
            'reload',
            'text',
            'url',
            'slug',
            'anchor_text',
            'button_text',
            'html_margin',
            'js_template',
            'banner_bg',
            'banner_text_color',
            'customCSS',
            'customClass',
            'cookie_name',
            'cookie_value',
            'target',
            'block',
            'custom_script_block_body_exclude',
            'custom_script_block',
            'content_message_text',
            'content_message_button_text',
        ];

        foreach ($fields as $field) {
            yield $field => [$field];
        }
    }

    /**
     * @dataProvider fields
     */
    public function testTheFieldMarkupDoesNotChange(string $field): void
    {
        // An administrator could see the media buttons, the fields disable them
        \wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        \update_option('italy_cookie_choices', self::OPTIONS);
        $sut = new Settings();
        $sut->italy_cl_settings_init();

        \ob_start();
        $sut->{'italy_cl_option_' . $field}([]);
        // The editor prints its stylesheets only on the first render of the process
        $output = (string) \preg_replace("/<link rel='stylesheet'[^>]*>\\n?/", '', (string) \ob_get_clean());

        $this->assertMatchesSnapshot($field, $output);
    }

    private function assertMatchesSnapshot(string $name, string $actual): void
    {
        $file = self::SNAPSHOTS . $name . '.html';
        if (!\is_file($file)) {
            if (!\is_dir(self::SNAPSHOTS)) {
                \mkdir(self::SNAPSHOTS, 0777, true);
            }
            \file_put_contents($file, $actual);
            $this->markTestIncomplete('Snapshot created: ' . $name);
        }

        $this->assertSame(\file_get_contents($file), $actual);
    }
}
