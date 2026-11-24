<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\ConsentLog\Application\LogSetting;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Cookies\Application\CookieSetting;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;

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
    public const VERSION = 11;

    public const COOKIE_NAME = 'displayCookieConsent';
    public const COOKIE_VALUE = 'y';

    private const CHECKBOXES = [
        PluginSetting::ACTIVE,
        ConsentSetting::RELOAD,
        ConsentSetting::HTML_MARGIN,
        ConsentSetting::LINKS_IN_NEW_TAB,
        ConsentSetting::LINKS_NOFOLLOW,
        BlockingSetting::WHOLE_PAGE,
        LogSetting::ACTIVE,
        IntegrationSetting::CONSENT_MODE,
        IntegrationSetting::CONSENT_MODE_ADVANCED,
        IntegrationSetting::YOUTUBE_NOCOOKIE,
        IntegrationSetting::UET_CONSENT_MODE,
        IntegrationSetting::CLARITY_CONSENT_MODE,
        IntegrationSetting::AMAZON_CONSENT_MODE,
        ConsentSetting::FLOATING_BUTTON,
        ConsentSetting::GLOBAL_PRIVACY_CONTROL,
        BlockingSetting::VENDOR_CONSENT,
    ];

    private const ENUMS = [
        ConsentSetting::POSITION => ['1', '2', '3', '4', '5'],
        ConsentSetting::TEMPLATE => ['default', 'bigbutton', 'smallbutton', 'custom'],
        LogSetting::RETENTION => ['3', '6', '12', '24', '36'],
        ConsentSetting::CONSENT_DURATION => ['30', '90', '182', '365'],
        ConsentSetting::FLOATING_BUTTON_POSITION => ['left', 'right'],
    ];

    private const STRINGS = [
        ConsentSetting::TEXT,
        ConsentSetting::POLICY_URL,
        ConsentSetting::POLICY_PAGE,
        ConsentSetting::POLICY_TEXT,
        ConsentSetting::ACCEPT_BUTTON_TEXT,
        ConsentSetting::REJECT_BUTTON_TEXT,
        ConsentSetting::PREFERENCES_BUTTON_TEXT,
        ConsentSetting::SAVE_BUTTON_TEXT,
        ConsentSetting::LEGAL_NOTICE_PAGE,
        ConsentSetting::LEGAL_NOTICE_URL,
        ConsentSetting::LEGAL_NOTICE_TEXT,
        ConsentSetting::BACKGROUND_COLOR,
        ConsentSetting::TEXT_COLOR,
        ConsentSetting::CUSTOM_CSS,
        ConsentSetting::BANNER_CLASS,
        ConsentSetting::CONTENT_CLASS,
        ConsentSetting::TEXT_CLASS,
        ConsentSetting::LINK_CLASS,
        ConsentSetting::BUTTON_CLASS,
        BlockingSetting::PLACEHOLDER_TEXT,
        BlockingSetting::PLACEHOLDER_BUTTON_TEXT,
        ConsentSetting::EXCLUDED_PAGES,
        ConsentSetting::FLOATING_BUTTON_LABEL,
    ];

    /**
     * The texts each site of a network keeps when it uses the settings of the main site, for its language:
     * the TRANSLATABLE texts of Options and the texts of the categories.
     */
    public const SITE_TEXTS = [
        ConsentSetting::TEXT,
        ConsentSetting::POLICY_URL,
        ConsentSetting::POLICY_TEXT,
        ConsentSetting::ACCEPT_BUTTON_TEXT,
        ConsentSetting::REJECT_BUTTON_TEXT,
        ConsentSetting::PREFERENCES_BUTTON_TEXT,
        ConsentSetting::SAVE_BUTTON_TEXT,
        ConsentSetting::LEGAL_NOTICE_URL,
        ConsentSetting::LEGAL_NOTICE_TEXT,
        BlockingSetting::PLACEHOLDER_TEXT,
        BlockingSetting::PLACEHOLDER_BUTTON_TEXT,
        ConsentSetting::FLOATING_BUTTON_LABEL,
        ConsentSetting::CATEGORIES,
    ];

    /**
     * The settings that point to the content or to the domain of a site, never copied to another site.
     */
    public const SITE_CONTENT = [
        ConsentSetting::LOGO,
        ConsentSetting::EXCLUDED_PAGES,
        ConsentSetting::COOKIE_DOMAIN,
    ];

    /**
     * The ids of the Google and Facebook tags, empty when the tag is not printed.
     */
    public const TAG_IDS = [
        IntegrationSetting::GA_MEASUREMENT_ID => '^(G-[A-Z0-9]+)?$',
        IntegrationSetting::GTM_CONTAINER_ID => '^(GTM-[A-Z0-9]+)?$',
        IntegrationSetting::FACEBOOK_PIXEL_ID => '^([0-9]+)?$',
        // Not a tag, the two letters of the country are checked and stored in upper case like the ids.
        IntegrationSetting::AMAZON_COUNTRY => '^([A-Z]{2})?$',
    ];

    /**
     * The paths of the site, like /metrics, empty when they are not used.
     */
    /**
     * The attachments of the media library, by their id, empty when there is none.
     */
    public const ATTACHMENTS = [
        ConsentSetting::LOGO => '^([1-9][0-9]*)?$',
    ];

    /**
     * The domain of the consent cookie, empty for the host of the site only.
     */
    public const DOMAINS = [
        ConsentSetting::COOKIE_DOMAIN => '^(\\.?[A-Za-z0-9-]+(\\.[A-Za-z0-9-]+)+)?$',
    ];

    public const PATHS = [
        BlockingSetting::GOOGLE_TAG_GATEWAY_PATH => '^(/[A-Za-z0-9._~-]+)*$',
    ];

    public const CONSENT_MODE_DEFAULTS = [
        IntegrationSetting::CONSENT_MODE => '1',
    ];

    /**
     * The presets of the vendors turned off on a new installation, the site owner turns them on.
     */
    public const VENDORS_OFF_BY_DEFAULT = [VendorId::GOOGLE_RECAPTCHA];

    public const MICROSOFT_CONSENT_DEFAULTS = [
        IntegrationSetting::UET_CONSENT_MODE => '1',
        IntegrationSetting::CLARITY_CONSENT_MODE => '1',
    ];

    /**
     * Accept all leaves out the marketing category when the browser sends the Global Privacy Control signal.
     */
    public const GLOBAL_PRIVACY_CONTROL_DEFAULTS = [
        ConsentSetting::GLOBAL_PRIVACY_CONTROL => '1',
    ];

    public const CONSENT_LOG_DEFAULTS = [
        LogSetting::ACTIVE => '1',
        LogSetting::RETENTION => '12',
    ];

    private const REQUIRED_STRINGS = [
        PluginSetting::LEGACY_COOKIE_NAME,
        PluginSetting::LEGACY_COOKIE_VALUE,
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
            ConsentSetting::TEXT => '',
            ConsentSetting::POLICY_URL => '',
            ConsentSetting::POLICY_TEXT => '',
            ConsentSetting::ACCEPT_BUTTON_TEXT => '',
            PluginSetting::LEGACY_COOKIE_NAME => self::COOKIE_NAME,
            PluginSetting::LEGACY_COOKIE_VALUE => self::COOKIE_VALUE,
            BlockingSetting::PLACEHOLDER_TEXT => '',
            BlockingSetting::PLACEHOLDER_BUTTON_TEXT => '',
            ...self::CONSENT_LOG_DEFAULTS,
            ...self::CONSENT_MODE_DEFAULTS,
            IntegrationSetting::YOUTUBE_NOCOOKIE => '1',
            ...self::MICROSOFT_CONSENT_DEFAULTS,
            BlockingSetting::DISABLED_VENDORS => self::VENDORS_OFF_BY_DEFAULT,
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
            ConsentSetting::CATEGORIES => [
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
            BlockingSetting::DISABLED_VENDORS => ['type' => 'array', 'items' => $id],
            BlockingSetting::CUSTOM_VENDORS => [
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
            CookieSetting::SITE_COOKIES => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [...$cookie, 'category' => $id],
                    'required' => ['name', 'category'],
                    'additionalProperties' => false,
                ],
            ],
            BlockingSetting::ALLOWED_PATTERNS => $texts,
            BlockingSetting::CUSTOM_SCRIPTS => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'category' => $id,
                        'position' => ['type' => 'string', 'enum' => ['head', 'body']],
                        'code' => ['type' => 'string', 'minLength' => 1],
                    ],
                    'required' => ['category', 'position', 'code'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
