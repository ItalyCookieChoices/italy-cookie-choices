<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Admin;

use Italy_Cookie_Choices\Admin\Settings;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SanitizeCallbackTest extends WPTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_settings_errors'] = [];
    }

    public function testEmptyInputGetsTheDefaults(): void
    {
        $this->assertSame(
            [
                'banner_bg' => '#fff',
                'banner_text_color' => '#000',
                'bannerStyle' => 'bannerStyle',
                'contentStyle' => 'contentStyle',
                'consentText' => 'consentText',
                'infoClass' => 'italybtn',
                'closeClass' => 'italybtn',
                'cookie_name' => 'displayCookieConsent',
                'cookie_value' => 'y',
            ],
            (new Settings())->sanitize_callback([])
        );
    }

    public function testEmptyCookieNameAndValueRaiseSettingsErrors(): void
    {
        (new Settings())->sanitize_callback(['cookie_name' => '', 'cookie_value' => '']);

        $this->assertSame(
            [
                [
                    'setting' => 'italy_cookie_id',
                    'code' => 'cookie_name_ID',
                    'message' => 'Cookie name field it can\'t be empty. Restored default name.',
                    'type' => 'error',
                ],
                [
                    'setting' => 'italy_cookie_id',
                    'code' => 'cookie_name_ID',
                    'message' => 'Cookie value field it can\'t be empty. Restored default value.',
                    'type' => 'error',
                ],
            ],
            \get_settings_errors('italy_cookie_id')
        );
    }

    public function testValidCookieNameAndValueRaiseNoErrors(): void
    {
        (new Settings())->sanitize_callback(['cookie_name' => 'name', 'cookie_value' => 'value']);

        $this->assertSame([], \get_settings_errors('italy_cookie_id'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function passThroughFields(): iterable
    {
        $fields = [
            'active',
            'banner',
            'scroll',
            'secondView',
            'reload',
            'html_margin',
            'js_template',
            'target',
            'block',
            'widget_block',
            'all_block',
            'custom_script_block_body_exclude',
            'custom_script_block',
        ];

        foreach ($fields as $field) {
            yield $field => [$field];
        }
    }

    /**
     * @dataProvider passThroughFields
     */
    public function testFieldsArePassedThroughUntouched(string $field): void
    {
        $value = "<script>raw</script>\n";

        $result = (new Settings())->sanitize_callback([$field => $value]);

        $this->assertSame($value, $result[$field]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function arrayFields(): iterable
    {
        $fields = ['allow_iframe', 'allow_script', 'allow_embed', 'block_iframe', 'block_script', 'block_embed'];

        foreach ($fields as $field) {
            yield $field => [$field];
        }
    }

    /**
     * @dataProvider arrayFields
     */
    public function testScriptListsArePassedThroughUntouched(string $field): void
    {
        $result = (new Settings())->sanitize_callback([$field => ['youtube', '<b>x</b>']]);

        $this->assertSame(['youtube', '<b>x</b>'], $result[$field]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function textFields(): iterable
    {
        $fields = [
            'url',
            'slug',
            'anchor_text',
            'button_text',
            'banner_bg',
            'banner_text_color',
            'customCSS',
            'bannerStyle',
            'contentStyle',
            'consentText',
            'infoClass',
            'closeClass',
            'cookie_name',
            'cookie_value',
            'content_message_button_text',
        ];

        foreach ($fields as $field) {
            yield $field => [$field];
        }
    }

    /**
     * @dataProvider textFields
     */
    public function testTextFieldsAreSanitized(string $field): void
    {
        $result = (new Settings())->sanitize_callback([$field => "  <b>bold</b>\n  text  "]);

        $this->assertSame('bold text', $result[$field]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function htmlFields(): iterable
    {
        yield 'text' => ['text'];
        yield 'content_message_text' => ['content_message_text'];
    }

    /**
     * @dataProvider htmlFields
     */
    public function testHtmlFieldsKeepThePostHtml(string $field): void
    {
        $result = (new Settings())->sanitize_callback([
            $field => '<a href="https://example.com">policy</a><script>alert(1)</script>',
        ]);

        $this->assertSame('<a href="https://example.com">policy</a>alert(1)', $result[$field]);
    }

    public function testMissingOptionalFieldsAreNotAdded(): void
    {
        $result = (new Settings())->sanitize_callback([]);

        $fields = ['active', 'text', 'url', 'slug', 'customCSS', 'block', 'allow_iframe', 'content_message_text'];

        foreach ($fields as $field) {
            $this->assertArrayNotHasKey($field, $result);
        }
    }

    public function testTranslatableStringsAreRegistered(): void
    {
        require_once \codecept_root_dir('tests/_support/multilingual-stub.php');
        $GLOBALS['icc_registered_strings'] = [];

        (new Settings())->sanitize_callback([
            'text' => 'Text',
            'url' => 'https://example.com',
            'slug' => 'policy',
            'anchor_text' => 'Anchor',
            'button_text' => 'Button',
            'content_message_text' => 'Message',
            'content_message_button_text' => 'Message button',
        ]);

        $this->assertSame(
            [
                ['Italy Cookie Choices', 'Banner text', 'Text'],
                ['Italy Cookie Choices', 'Banner url', 'https://example.com'],
                ['Italy Cookie Choices', 'Banner slug', 'policy'],
                ['Italy Cookie Choices', 'Banner anchor text', 'Anchor'],
                ['Italy Cookie Choices', 'Banner button text', 'Button'],
                ['Italy Cookie Choices', 'Content message text', 'Message'],
                ['Italy Cookie Choices', 'Content message button text', 'Message button'],
            ],
            $GLOBALS['icc_registered_strings']
        );
    }

    public function testSavingTheOptionRunsTheSanitizeCallback(): void
    {
        (new Settings())->italy_cl_settings_init();

        \update_option('italy_cookie_choices', ['active' => '1', 'anchor_text' => '<b>More</b>']);

        $saved = \get_option('italy_cookie_choices');
        $this->assertIsArray($saved);
        $this->assertSame('1', $saved['active']);
        $this->assertSame('More', $saved['anchor_text']);
        $this->assertSame('displayCookieConsent', $saved['cookie_name']);
    }
}
