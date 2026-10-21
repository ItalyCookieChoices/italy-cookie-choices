<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\Schema;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SchemaTest extends WPTestCase
{
    /**
     * Every key the 2.x versions read from the option.
     */
    private const LEGACY_KEYS = [
        'active', 'all_block', 'allow_embed', 'allow_iframe', 'allow_script', 'anchor_text', 'banner',
        'bannerStyle', 'banner_bg', 'banner_text_color', 'block', 'block_embed', 'block_iframe', 'block_script',
        'button_text', 'closeClass', 'consentText', 'contentStyle', 'content_message_button_text',
        'content_message_text', 'cookie_name', 'cookie_value', 'customCSS', 'custom_script_block',
        'custom_script_block_body_exclude', 'html_margin', 'infoClass', 'js_template', 'reload', 'scroll',
        'secondView', 'slug', 'target', 'text', 'url', 'widget_block',
    ];

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
            ],
            (new Schema())->defaults()
        );
    }

    public function testEveryLegacyKeyIsInTheSchema(): void
    {
        $properties = \array_keys((new Schema())->toJsonSchema()['properties']);
        \sort($properties);

        $this->assertSame(self::LEGACY_KEYS, $properties);
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
        yield 'banner out of range' => [['banner' => '4']];
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
