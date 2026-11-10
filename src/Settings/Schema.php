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
    public const VERSION = 10;

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
        'consent_mode',
        'consent_mode_advanced',
        'youtube_nocookie',
        'uet_consent_mode',
        'clarity_consent_mode',
        'floating_button',
        'global_privacy_control',
    ];

    private const ENUMS = [
        'banner' => ['1', '2', '3', '4', '5'],
        'js_template' => ['default', 'bigbutton', 'smallbutton', 'custom'],
        'consent_log_retention' => ['3', '6', '12', '24', '36'],
        'floating_button_position' => ['left', 'right'],
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
        'floating_button_label',
    ];

    /**
     * The texts each site of a network keeps when it uses the settings of the main site, for its language:
     * the TRANSLATABLE texts of Options and the texts of the categories.
     */
    public const SITE_TEXTS = [
        'text',
        'url',
        'anchor_text',
        'button_text',
        'content_message_text',
        'content_message_button_text',
        'floating_button_label',
        'categories',
    ];

    /**
     * The ids of the Google and Facebook tags, empty when the tag is not printed.
     */
    public const TAG_IDS = [
        'ga_measurement_id' => '^(G-[A-Z0-9]+)?$',
        'gtm_container_id' => '^(GTM-[A-Z0-9]+)?$',
        'facebook_pixel_id' => '^([0-9]+)?$',
    ];

    /**
     * The paths of the site, like /metrics, empty when they are not used.
     */
    /**
     * The attachments of the media library, by their id, empty when there is none.
     */
    public const ATTACHMENTS = [
        'banner_logo' => '^([1-9][0-9]*)?$',
    ];

    /**
     * The domain of the consent cookie, empty for the host of the site only.
     */
    public const DOMAINS = [
        'cookie_domain' => '^(\\.?[A-Za-z0-9-]+(\\.[A-Za-z0-9-]+)+)?$',
    ];

    public const PATHS = [
        'google_tag_gateway_path' => '^(/[A-Za-z0-9._~-]+)*$',
    ];

    public const CONSENT_MODE_DEFAULTS = [
        'consent_mode' => '1',
    ];

    /**
     * The presets of the vendors turned off on a new installation, the site owner turns them on.
     */
    public const VENDORS_OFF_BY_DEFAULT = ['google-recaptcha'];

    public const MICROSOFT_CONSENT_DEFAULTS = [
        'uet_consent_mode' => '1',
        'clarity_consent_mode' => '1',
    ];

    /**
     * Accept all leaves out the marketing category when the browser sends the Global Privacy Control signal.
     */
    public const GLOBAL_PRIVACY_CONTROL_DEFAULTS = [
        'global_privacy_control' => '1',
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
     * @return array<string, string|list<string>>
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
            ...self::CONSENT_MODE_DEFAULTS,
            'youtube_nocookie' => '1',
            ...self::MICROSOFT_CONSENT_DEFAULTS,
            'disabled_vendors' => self::VENDORS_OFF_BY_DEFAULT,
            ...self::GLOBAL_PRIVACY_CONTROL_DEFAULTS,
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

        foreach (self::TAG_IDS as $name => $pattern) {
            $properties[$name] = ['type' => 'string', 'pattern' => $pattern];
        }

        foreach (self::ATTACHMENTS as $name => $pattern) {
            $properties[$name] = ['type' => 'string', 'pattern' => $pattern];
        }

        foreach (self::DOMAINS as $name => $pattern) {
            $properties[$name] = ['type' => 'string', 'pattern' => $pattern];
        }

        foreach (self::PATHS as $name => $pattern) {
            $properties[$name] = ['type' => 'string', 'pattern' => $pattern];
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
        $cookie = [
            'name' => ['type' => 'string', 'minLength' => 1],
            'duration' => ['type' => 'string'],
            'purpose' => ['type' => 'string'],
        ];

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
                        'cookies' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => $cookie,
                                'required' => ['name'],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                    'required' => ['id', 'name', 'category', 'patterns'],
                    'additionalProperties' => false,
                ],
            ],
            'site_cookies' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [...$cookie, 'category' => $id],
                    'required' => ['name', 'category'],
                    'additionalProperties' => false,
                ],
            ],
            'allowed_patterns' => $texts,
        ];
    }
}
