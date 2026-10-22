<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\Schema;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SchemaTest extends WPTestCase
{
    /**
     * Every key the 2.x versions read from the option, without scroll and secondView removed in version 2,
     * plus the ones added in version 2.
     */
    private const KEYS = [
        'active', 'all_block', 'allow_embed', 'allow_iframe', 'allow_script', 'anchor_text', 'banner',
        'bannerStyle', 'banner_bg', 'banner_text_color', 'block_embed', 'block_iframe', 'block_script',
        'button_text', 'closeClass', 'consentText', 'consent_log', 'consent_log_retention', 'contentStyle',
        'content_message_button_text', 'content_message_text', 'cookie_name', 'cookie_value', 'customCSS',
        'excluded_pages', 'html_margin', 'infoClass', 'js_template', 'nofollow', 'reload', 'slug', 'target', 'text',
        'url',
    ];

    public function testTheSchemaIsAtVersion4(): void
    {
        $this->assertSame(4, Schema::VERSION);
    }

    public function testTheDefaultsAreTheOnesOfTheNewInstallations(): void
    {
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
                'consent_log' => '1',
                'consent_log_retention' => '12',
            ],
            (new Schema())->defaults()
        );
    }

    public function testEveryKeyIsInTheSchema(): void
    {
        $properties = \array_keys((new Schema())->toJsonSchema()['properties']);
        \sort($properties);

        $this->assertSame(self::KEYS, $properties);
    }

    public function testTheDefaultsAreValid(): void
    {
        $schema = new Schema();

        $this->assertTrue(\rest_validate_value_from_schema($schema->defaults(), $schema->toJsonSchema()));
    }

    public function testASavedOptionIsValid(): void
    {
        $option = [
            'active' => '1',
            'banner' => '2',
            'js_template' => 'bigbutton',
            'text' => '<a href="/policy">Policy</a>',
            'cookie_name' => 'name',
            'cookie_value' => 'value',
            'allow_iframe' => ['youtube.com' => 'youtube.com'],
            'nofollow' => '1',
            'excluded_pages' => '42, contact',
        ];

        $this->assertTrue(\rest_validate_value_from_schema($option, (new Schema())->toJsonSchema()));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public function invalidOptions(): iterable
    {
        yield 'unknown key' => [['unknown' => '1']];
        yield 'checkbox not 1' => [['active' => 'yes']];
        yield 'nofollow not 1' => [['nofollow' => 'yes']];
        yield 'excluded pages not a string' => [['excluded_pages' => [42]]];
        yield 'scroll removed in version 2' => [['scroll' => '1']];
        yield 'second view removed in version 2' => [['secondView' => '1']];
        yield 'content blocking always on in version 3' => [['block' => '1']];
        yield 'widget blocking always on in version 3' => [['widget_block' => '1']];
        yield 'snippets moved to the lists in version 3' => [['custom_script_block' => '<script></script>']];
        yield 'banner out of range' => [['banner' => '4']];
        yield 'consent log retention not listed' => [['consent_log_retention' => '7']];
        yield 'unknown template' => [['js_template' => 'other']];
        yield 'empty cookie name' => [['cookie_name' => '']];
        yield 'empty cookie value' => [['cookie_value' => '']];
        yield 'text not a string' => [['text' => ['a']]];
        yield 'script list with a list' => [['block_script' => [['a']]]];
    }

    /**
     * @dataProvider invalidOptions
     * @param array<string, mixed> $option
     */
    public function testInvalidValuesAreRejected(array $option): void
    {
        $this->assertWPError(\rest_validate_value_from_schema($option, (new Schema())->toJsonSchema()));
    }
}
