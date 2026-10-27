<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\Schema;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SchemaTest extends WPTestCase
{
    /**
     * Every key the 2.x versions read from the option, without scroll and secondView removed in version 2,
     * plus the consent log of version 4, the vendors of version 5 that replaced the script lists
     * and the integrations of version 6.
     */
    private const KEYS = [
        'active', 'all_block', 'allowed_patterns', 'anchor_text', 'banner', 'bannerStyle', 'banner_bg',
        'banner_text_color', 'button_text', 'categories', 'closeClass', 'consentText', 'consent_log',
        'consent_log_retention', 'consent_mode', 'consent_mode_advanced', 'contentStyle',
        'content_message_button_text', 'content_message_text', 'cookie_name', 'cookie_value', 'customCSS',
        'custom_vendors', 'disabled_vendors', 'excluded_pages', 'facebook_pixel_id', 'ga_measurement_id',
        'gtm_container_id', 'html_margin', 'infoClass', 'js_template', 'nofollow', 'reload', 'slug', 'target',
        'text', 'url', 'youtube_nocookie',
    ];

    public function testTheSchemaIsAtVersion6(): void
    {
        $this->assertSame(6, Schema::VERSION);
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
                'consent_mode' => '1',
                'youtube_nocookie' => '1',
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
            'nofollow' => '1',
            'excluded_pages' => '42, contact',
            'categories' => ['statistics' => ['label' => 'Analytics', 'description' => 'Visits.']],
            'disabled_vendors' => ['youtube'],
            'custom_vendors' => [
                ['id' => 'custom', 'name' => 'Custom', 'category' => 'marketing', 'patterns' => ['a.example.com']],
            ],
            'allowed_patterns' => ['youtube.com/embed/public'],
            'consent_mode' => '1',
            'ga_measurement_id' => 'G-ABC123',
            'gtm_container_id' => '',
            'facebook_pixel_id' => '123456',
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
        yield 'script lists moved to the vendors in version 5' => [['block_script' => ['a' => 'a']]];
        yield 'category id not valid' => [['categories' => ['Not Valid' => ['label' => 'a']]]];
        yield 'category with an unknown text' => [['categories' => ['statistics' => ['title' => 'a']]]];
        yield 'disabled vendor id not valid' => [['disabled_vendors' => ['Not Valid']]];
        yield 'custom vendor without patterns' => [
            ['custom_vendors' => [['id' => 'a', 'name' => 'A', 'category' => 'marketing']]],
        ];
        yield 'custom vendor with an invalid category' => [
            ['custom_vendors' => [['id' => 'a', 'name' => 'A', 'category' => 'Ads', 'patterns' => ['a']]]],
        ];
        yield 'allowed pattern not a string' => [['allowed_patterns' => [['a']]]];
        yield 'measurement id of another tag' => [['ga_measurement_id' => 'UA-123-1']];
        yield 'container id in lower case' => [['gtm_container_id' => 'gtm-abc']];
        yield 'pixel id with letters' => [['facebook_pixel_id' => 'abc']];
        yield 'advanced consent mode not 1' => [['consent_mode_advanced' => 'yes']];
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
