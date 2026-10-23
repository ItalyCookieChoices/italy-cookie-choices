<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

/**
 * The shape of the plugin option.
 *
 * A checkbox is on when its key exists with the '1' value.
 * The categories hold the texts that replace the default ones, keyed by category id.
 * The vendors are the presets turned off, the custom vendors and the texts never blocked.
 */
final readonly class Schema
{
    public const OPTION = 'italy_cookie_choices';
    public const GROUP = 'italy_cl_options_group';
    public const VERSION_OPTION = 'italy_cookie_choices_version';
    public const VERSION = 5;

    public const COOKIE_NAME = 'displayCookieConsent';
    public const COOKIE_VALUE = 'y';

    private const CHECKBOXES = [
        'active',
        'reload',
        'html_margin',
        'target',
        'nofollow',
        'all_block',
        'consent_log',
    ];

    private const ENUMS = [
        'banner' => ['1', '2', '3'],
        'js_template' => ['default', 'bigbutton', 'smallbutton', 'custom'],
        'consent_log_retention' => ['3', '6', '12', '24', '36'],
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

    public const CONSENT_LOG_DEFAULTS = [
        'consent_log' => '1',
        'consent_log_retention' => '12',
    ];

    private const REQUIRED_STRINGS = [
        'cookie_name',
        'cookie_value',
    ];

    private const ID_PATTERN = '^[a-z0-9_-]+$';

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
            ...self::CONSENT_LOG_DEFAULTS,
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

        return [
            'type' => 'object',
            'properties' => [...$properties, ...$this->vendorProperties()],
            'additionalProperties' => false,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function vendorProperties(): array
    {
        $texts = ['type' => 'array', 'items' => ['type' => 'string']];
        $id = ['type' => 'string', 'pattern' => self::ID_PATTERN];

        return [
            'categories' => [
                'type' => 'object',
                'patternProperties' => [
                    self::ID_PATTERN => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                        ],
                        'additionalProperties' => false,
                    ],
                ],
                'additionalProperties' => false,
            ],
            'disabled_vendors' => ['type' => 'array', 'items' => $id],
            'custom_vendors' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => $id,
                        'name' => ['type' => 'string'],
                        'category' => $id,
                        'patterns' => $texts,
                    ],
                    'required' => ['id', 'name', 'category', 'patterns'],
                    'additionalProperties' => false,
                ],
            ],
            'allowed_patterns' => $texts,
        ];
    }
}
