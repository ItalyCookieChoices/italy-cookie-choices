<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

/**
 * The shape of the plugin option.
 *
 * A checkbox is on when its key exists with the '1' value, the script lists are keyed by value.
 */
final readonly class Schema
{
    public const OPTION = 'italy_cookie_choices';
    public const GROUP = 'italy_cl_options_group';
    public const VERSION_OPTION = 'italy_cookie_choices_version';
    public const VERSION = 3;

    public const COOKIE_NAME = 'displayCookieConsent';
    public const COOKIE_VALUE = 'y';

    private const CHECKBOXES = [
        'active',
        'reload',
        'html_margin',
        'target',
        'nofollow',
        'all_block',
    ];

    private const ENUMS = [
        'banner' => ['1', '2', '3'],
        'js_template' => ['default', 'bigbutton', 'smallbutton', 'custom'],
    ];

    private const STRINGS = [
        'text',
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
        'content_message_text',
        'content_message_button_text',
        'excluded_pages',
    ];

    private const REQUIRED_STRINGS = [
        'cookie_name',
        'cookie_value',
    ];

    private const SCRIPT_LISTS = [
        'allow_iframe',
        'allow_script',
        'allow_embed',
        'block_iframe',
        'block_script',
        'block_embed',
    ];

    /**
     * The values stored on a new installation.
     *
     * @return array<string, string>
     */
    public function defaults(): array
    {
        return [
            'text' => '',
            'url' => '',
            'anchor_text' => '',
            'button_text' => '',
            'cookie_name' => self::COOKIE_NAME,
            'cookie_value' => self::COOKIE_VALUE,
            'content_message_text' => '',
            'content_message_button_text' => '',
        ];
    }

    /**
     * The JSON schema used by the REST API to validate the option.
     *
     * @return array{type: string, properties: array<string, array<string, mixed>>, additionalProperties: false}
     */
    public function toJsonSchema(): array
    {
        $properties = [];

        foreach (self::CHECKBOXES as $name) {
            $properties[$name] = ['type' => 'string', 'enum' => ['1']];
        }

        foreach (self::ENUMS as $name => $values) {
            $properties[$name] = ['type' => 'string', 'enum' => $values];
        }

        foreach (self::STRINGS as $name) {
            $properties[$name] = ['type' => 'string'];
        }

        foreach (self::REQUIRED_STRINGS as $name) {
            $properties[$name] = ['type' => 'string', 'minLength' => 1];
        }

        foreach (self::SCRIPT_LISTS as $name) {
            $properties[$name] = ['type' => 'object', 'additionalProperties' => ['type' => 'string']];
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'additionalProperties' => false,
        ];
    }
}
