<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\ConsentLog\Application\LogSetting;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Cookies\Application\CookieSetting;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Consent\Domain\CookieDomain;

/**
 * Sanitizes the option saved from the settings page, the REST API or an import.
 */
final readonly class Sanitizer
{
    private const RAW = 'raw';
    private const TEXT = 'text';
    private const HTML = 'html';
    private const TEXT_OR_DEFAULT = 'text_or_default';
    private const REQUIRED = 'required';
    private const CATEGORIES = 'categories';
    private const IDS = 'ids';
    private const VENDORS = 'vendors';
    private const PATTERNS = 'patterns';
    private const TAG_ID = 'tag_id';
    private const PATH = 'path';
    private const ATTACHMENT = 'attachment';
    private const DOMAIN = 'domain';
    private const SITE_COOKIES = 'site_cookies';
    private const SCRIPTS = 'scripts';
    private const SCRIPT_POSITIONS = ['head', 'body'];

    /**
     * The fields in the order they are saved.
     */
    private const FIELDS = [
        PluginSetting::ACTIVE => self::RAW,
        ConsentSetting::POSITION => self::RAW,
        ConsentSetting::RELOAD => self::RAW,
        ConsentSetting::TEXT => self::HTML,
        ConsentSetting::POLICY_URL => self::TEXT,
        ConsentSetting::POLICY_PAGE => self::TEXT,
        ConsentSetting::POLICY_TEXT => self::TEXT,
        ConsentSetting::ACCEPT_BUTTON_TEXT => self::TEXT,
        ConsentSetting::REJECT_BUTTON_TEXT => self::TEXT,
        ConsentSetting::PREFERENCES_BUTTON_TEXT => self::TEXT,
        ConsentSetting::SAVE_BUTTON_TEXT => self::TEXT,
        ConsentSetting::LEGAL_NOTICE_PAGE => self::TEXT,
        ConsentSetting::LEGAL_NOTICE_URL => self::TEXT,
        ConsentSetting::LEGAL_NOTICE_TEXT => self::TEXT,
        ConsentSetting::HTML_MARGIN => self::RAW,
        ConsentSetting::TEMPLATE => self::RAW,
        ConsentSetting::BACKGROUND_COLOR => self::TEXT_OR_DEFAULT,
        ConsentSetting::TEXT_COLOR => self::TEXT_OR_DEFAULT,
        ConsentSetting::CUSTOM_CSS => self::TEXT,
        ConsentSetting::BANNER_CLASS => self::TEXT_OR_DEFAULT,
        ConsentSetting::CONTENT_CLASS => self::TEXT_OR_DEFAULT,
        ConsentSetting::TEXT_CLASS => self::TEXT_OR_DEFAULT,
        ConsentSetting::LINK_CLASS => self::TEXT_OR_DEFAULT,
        ConsentSetting::BUTTON_CLASS => self::TEXT_OR_DEFAULT,
        PluginSetting::LEGACY_COOKIE_NAME => self::REQUIRED,
        PluginSetting::LEGACY_COOKIE_VALUE => self::REQUIRED,
        ConsentSetting::LINKS_IN_NEW_TAB => self::RAW,
        ConsentSetting::LINKS_NOFOLLOW => self::RAW,
        BlockingSetting::WHOLE_PAGE => self::RAW,
        LogSetting::ACTIVE => self::RAW,
        LogSetting::RETENTION => self::RAW,
        ConsentSetting::CONSENT_DURATION => self::RAW,
        IntegrationSetting::CONSENT_MODE => self::RAW,
        IntegrationSetting::CONSENT_MODE_ADVANCED => self::RAW,
        IntegrationSetting::GA_MEASUREMENT_ID => self::TAG_ID,
        IntegrationSetting::GTM_CONTAINER_ID => self::TAG_ID,
        IntegrationSetting::FACEBOOK_PIXEL_ID => self::TAG_ID,
        IntegrationSetting::AMAZON_COUNTRY => self::TAG_ID,
        BlockingSetting::GOOGLE_TAG_GATEWAY_PATH => self::PATH,
        ConsentSetting::LOGO => self::ATTACHMENT,
        ConsentSetting::COOKIE_DOMAIN => self::DOMAIN,
        IntegrationSetting::YOUTUBE_NOCOOKIE => self::RAW,
        IntegrationSetting::UET_CONSENT_MODE => self::RAW,
        IntegrationSetting::CLARITY_CONSENT_MODE => self::RAW,
        IntegrationSetting::AMAZON_CONSENT_MODE => self::RAW,
        ConsentSetting::FLOATING_BUTTON => self::RAW,
        ConsentSetting::GLOBAL_PRIVACY_CONTROL => self::RAW,
        BlockingSetting::VENDOR_CONSENT => self::RAW,
        ConsentSetting::FLOATING_BUTTON_POSITION => self::RAW,
        ConsentSetting::FLOATING_BUTTON_LABEL => self::TEXT,
        ConsentSetting::CATEGORIES => self::CATEGORIES,
        BlockingSetting::DISABLED_VENDORS => self::IDS,
        BlockingSetting::CUSTOM_VENDORS => self::VENDORS,
        BlockingSetting::ALLOWED_PATTERNS => self::PATTERNS,
        CookieSetting::SITE_COOKIES => self::SITE_COOKIES,
        BlockingSetting::CUSTOM_SCRIPTS => self::SCRIPTS,
        BlockingSetting::PLACEHOLDER_TEXT => self::HTML,
        BlockingSetting::PLACEHOLDER_BUTTON_TEXT => self::TEXT,
        ConsentSetting::EXCLUDED_PAGES => self::TEXT,
    ];

    private const DEFAULTS = [
        ConsentSetting::BACKGROUND_COLOR => '#fff',
        ConsentSetting::TEXT_COLOR => '#000',
        ConsentSetting::BANNER_CLASS => 'bannerStyle',
        ConsentSetting::CONTENT_CLASS => 'contentStyle',
        ConsentSetting::TEXT_CLASS => 'consentText',
        ConsentSetting::LINK_CLASS => 'italybtn',
        ConsentSetting::BUTTON_CLASS => 'italybtn',
        PluginSetting::LEGACY_COOKIE_NAME => Schema::COOKIE_NAME,
        PluginSetting::LEGACY_COOKIE_VALUE => Schema::COOKIE_VALUE,
    ];

    private const CATEGORY_TEXTS = ['label', 'description'];

    /**
     * @return array<string, mixed>
     */
    public function __invoke(mixed $input): array
    {
        $input = \is_array($input) ? $input : [];
        $sanitized = [];

        foreach (self::FIELDS as $name => $rule) {
            $value = $rule === self::SCRIPTS && !$this->canSaveScripts() ? $this->saved($name) : $input[$name] ?? null;
            if ($value === null && !$this->hasDefault($rule)) {
                continue;
            }

            $sanitized[$name] = $this->sanitize($name, $rule, $value);
        }

        return $sanitized;
    }

    private function hasDefault(string $rule): bool
    {
        return $rule === self::TEXT_OR_DEFAULT || $rule === self::REQUIRED;
    }

    private function sanitize(string $name, string $rule, mixed $value): mixed
    {
        return match ($rule) {
            self::RAW => $value,
            self::TEXT => \sanitize_text_field($this->toString($value)),
            self::HTML => \wp_kses_post($this->toString($value)),
            self::TEXT_OR_DEFAULT => $this->textOrDefault($name, $value),
            self::CATEGORIES => $this->categories($value),
            self::IDS => $this->ids($value),
            self::VENDORS => $this->vendors($value),
            self::PATTERNS => $this->patterns($value),
            self::TAG_ID => $this->tagId($name, $value),
            self::PATH => $this->path($name, $value),
            self::ATTACHMENT => $this->attachment($value),
            self::DOMAIN => $this->domain($value),
            self::SITE_COOKIES => $this->siteCookies($value),
            self::SCRIPTS => $this->scripts($value),
            default => $this->required($name, $value),
        };
    }

    private function textOrDefault(string $name, mixed $value): string
    {
        if ($this->isEmpty($value)) {
            return self::DEFAULTS[$name];
        }

        return \sanitize_text_field($this->toString($value));
    }

    private function required(string $name, mixed $value): string
    {
        if (!$this->isEmpty($value)) {
            return \sanitize_text_field($this->toString($value));
        }

        $message = $name === PluginSetting::LEGACY_COOKIE_NAME
            ? \__('Cookie name field it can\'t be empty. Restored default name.', 'italy-cookie-choices')
            : \__('Cookie value field it can\'t be empty. Restored default value.', 'italy-cookie-choices');
        \add_settings_error('italy_cookie_id', 'cookie_name_ID', $message, 'error');

        return self::DEFAULTS[$name];
    }

    /**
     * The texts that replace the default ones, keyed by category id, without the empty ones.
     *
     * @return array<string, array<string, string>>
     */
    private function categories(mixed $value): array
    {
        $categories = [];
        foreach (\is_array($value) ? $value : [] as $id => $texts) {
            $id = \sanitize_key((string) $id);
            $texts = $this->categoryTexts($texts);
            if ($id === '' || $texts === []) {
                continue;
            }

            $categories[$id] = $texts;
        }

        return $categories;
    }

    /**
     * @return array<string, string>
     */
    private function categoryTexts(mixed $value): array
    {
        $texts = [];
        foreach (self::CATEGORY_TEXTS as $name) {
            $text = \sanitize_textarea_field($this->toString(\is_array($value) ? $value[$name] ?? '' : ''));
            if ($text === '') {
                continue;
            }

            $texts[$name] = $text;
        }

        return $texts;
    }

    /**
     * The id in upper case, empty when it has not the shape of the tag, like G-ABC123 for Google Analytics.
     */
    private function tagId(string $name, mixed $value): string
    {
        $id = \strtoupper(\trim($this->toString($value)));

        return \preg_match('/' . Schema::TAG_IDS[$name] . '/', $id) === 1 ? $id : '';
    }

    /**
     * The path of an address or of a text, with the leading slash and without the trailing one,
     * empty when it is the root or it has not the shape of a path, like /metrics.
     */
    /**
     * The id of an attachment, empty when it is not a positive number.
     */
    private function attachment(mixed $value): string
    {
        $id = \is_int($value) ? (string) $value : \trim($this->toString($value));

        return \preg_match('/^[1-9][0-9]*$/', $id) === 1 ? $id : '';
    }

    /**
     * The domain of the consent cookie, empty when the browsers would refuse it on this site.
     */
    private function domain(mixed $value): string
    {
        return (new CookieDomain($this->toString($value)))
            ->forHost((string) \wp_parse_url(\home_url(), \PHP_URL_HOST));
    }

    private function path(string $name, mixed $value): string
    {
        $path = (string) \wp_parse_url(\trim($this->toString($value)), \PHP_URL_PATH);
        $path = '/' . \trim($path, '/');
        if ($path === '/') {
            return '';
        }

        return \preg_match('#' . Schema::PATHS[$name] . '#', $path) === 1 ? $path : '';
    }

    /**
     * @return list<string>
     */
    private function ids(mixed $value): array
    {
        $ids = \array_map(fn(mixed $id): string => \sanitize_key($this->toString($id)), $this->list($value));

        return \array_values(\array_unique(\array_filter($ids, static fn(string $id): bool => $id !== '')));
    }

    /**
     * The custom vendors with at least a pattern, each with a unique id.
     *
     * @return list<array{id: string, name: string, category: string, patterns: list<string>}>
     */
    private function vendors(mixed $value): array
    {
        $vendors = [];
        foreach ($this->list($value) as $vendor) {
            $vendor = \is_array($vendor) ? $vendor : [];
            $name = \sanitize_text_field($this->toString($vendor['name'] ?? ''));
            $category = \sanitize_key($this->toString($vendor['category'] ?? ''));
            $patterns = $this->patterns($vendor['patterns'] ?? []);
            if ($category === '' || $patterns === []) {
                continue;
            }

            $id = $this->toString($vendor['id'] ?? '');
            $id = $this->uniqueId($id !== '' ? $id : $name, \array_column($vendors, 'id'));
            $vendors[] = [
                'id' => $id,
                'name' => $name,
                'category' => $category,
                'patterns' => $patterns,
                'cookies' => $this->cookies($vendor['cookies'] ?? []),
            ];
        }

        return $vendors;
    }

    /**
     * The cookies of a custom vendor, without the ones with no name.
     *
     * @return list<array{name: string, duration: string, purpose: string}>
     */
    private function cookies(mixed $value): array
    {
        $cookies = [];
        foreach ($this->list($value) as $cookie) {
            $cookie = $this->cookie($cookie);
            if ($cookie === null) {
                continue;
            }

            $cookies[] = $cookie;
        }

        return $cookies;
    }

    /**
     * The cookies of the site, without the ones with no name or no category.
     *
     * @return list<array{name: string, category: string, duration: string, purpose: string}>
     */
    private function siteCookies(mixed $value): array
    {
        $cookies = [];
        foreach ($this->list($value) as $cookie) {
            $category = \sanitize_key($this->toString(\is_array($cookie) ? $cookie['category'] ?? '' : ''));
            $cookie = $this->cookie($cookie);
            if ($cookie === null || $category === '') {
                continue;
            }

            $cookies[] = ['name' => $cookie['name'], 'category' => $category, ...\array_slice($cookie, 1)];
        }

        return $cookies;
    }

    /**
     * The custom scripts with a category and their code, kept as it is: only the users who can save
     * unfiltered HTML change them.
     *
     * @return list<array{name: string, category: string, position: string, code: string}>
     */
    private function scripts(mixed $value): array
    {
        $scripts = [];
        foreach ($this->list($value) as $script) {
            $script = \is_array($script) ? $script : [];
            $category = \sanitize_key($this->toString($script['category'] ?? ''));
            $code = \trim(\wp_check_invalid_utf8($this->toString($script['code'] ?? '')));
            if ($category === '' || $code === '') {
                continue;
            }

            $position = $this->toString($script['position'] ?? '');
            $scripts[] = [
                'name' => \sanitize_text_field($this->toString($script['name'] ?? '')),
                'category' => $category,
                'position' => \in_array($position, self::SCRIPT_POSITIONS, true) ? $position : 'body',
                'code' => $code,
            ];
        }

        return $scripts;
    }

    /**
     * Like the Custom HTML block: the administrators of a site, the super admins of a network, and WP-CLI.
     */
    private function canSaveScripts(): bool
    {
        return \current_user_can('unfiltered_html') || (\defined('WP_CLI') && \WP_CLI);
    }

    /**
     * The saved value of a field, null when it is not saved.
     */
    private function saved(string $name): mixed
    {
        $saved = \get_option(Schema::OPTION);

        return \is_array($saved) ? $saved[$name] ?? null : null;
    }

    /**
     * @return array{name: string, duration: string, purpose: string}|null
     */
    private function cookie(mixed $value): ?array
    {
        if (!\is_array($value)) {
            return null;
        }

        $name = \sanitize_text_field($this->toString($value['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        return [
            'name' => $name,
            'duration' => \sanitize_text_field($this->toString($value['duration'] ?? '')),
            'purpose' => \sanitize_text_field($this->toString($value['purpose'] ?? '')),
        ];
    }

    /**
     * @param list<string> $taken
     */
    private function uniqueId(string $id, array $taken): string
    {
        $id = \sanitize_key($id);
        $id = $id !== '' ? $id : Migration::CUSTOM_VENDOR;
        $unique = $id;
        $suffix = 2;
        while (\in_array($unique, $taken, true)) {
            $unique = $id . '-' . $suffix++;
        }

        return $unique;
    }

    /**
     * The texts of the URLs and the inline scripts, on one line.
     *
     * Not sanitize_text_field(), it removes the percent-encoded characters of the URLs.
     *
     * @return list<string>
     */
    private function patterns(mixed $value): array
    {
        $patterns = \array_map(
            fn(mixed $pattern): string => \trim((string) \preg_replace(
                '/\s+/',
                ' ',
                \wp_check_invalid_utf8($this->toString($pattern))
            )),
            $this->list($value)
        );

        return \array_values(\array_unique(\array_filter(
            $patterns,
            static fn(string $pattern): bool => $pattern !== ''
        )));
    }

    /**
     * @return list<mixed>
     */
    private function list(mixed $value): array
    {
        return \is_array($value) ? \array_values($value) : [];
    }

    /**
     * The same values empty() considers empty, the 2.x versions restored the defaults for them.
     */
    private function isEmpty(mixed $value): bool
    {
        return \in_array($value, [null, false, 0, 0.0, '', '0', []], true);
    }

    private function toString(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : '';
    }
}
