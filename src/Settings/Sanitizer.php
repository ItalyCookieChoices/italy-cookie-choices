<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

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
    private const SITE_COOKIES = 'site_cookies';

    /**
     * The fields in the order they are saved.
     */
    private const FIELDS = [
        'active' => self::RAW,
        'banner' => self::RAW,
        'reload' => self::RAW,
        'text' => self::HTML,
        'url' => self::TEXT,
        'slug' => self::TEXT,
        'anchor_text' => self::TEXT,
        'button_text' => self::TEXT,
        'html_margin' => self::RAW,
        'js_template' => self::RAW,
        'banner_bg' => self::TEXT_OR_DEFAULT,
        'banner_text_color' => self::TEXT_OR_DEFAULT,
        'customCSS' => self::TEXT,
        'bannerStyle' => self::TEXT_OR_DEFAULT,
        'contentStyle' => self::TEXT_OR_DEFAULT,
        'consentText' => self::TEXT_OR_DEFAULT,
        'infoClass' => self::TEXT_OR_DEFAULT,
        'closeClass' => self::TEXT_OR_DEFAULT,
        'cookie_name' => self::REQUIRED,
        'cookie_value' => self::REQUIRED,
        'target' => self::RAW,
        'nofollow' => self::RAW,
        'all_block' => self::RAW,
        'consent_log' => self::RAW,
        'consent_log_retention' => self::RAW,
        'consent_mode' => self::RAW,
        'consent_mode_advanced' => self::RAW,
        'ga_measurement_id' => self::TAG_ID,
        'gtm_container_id' => self::TAG_ID,
        'facebook_pixel_id' => self::TAG_ID,
        'google_tag_gateway_path' => self::PATH,
        'banner_logo' => self::ATTACHMENT,
        'youtube_nocookie' => self::RAW,
        'uet_consent_mode' => self::RAW,
        'clarity_consent_mode' => self::RAW,
        'floating_button' => self::RAW,
        'floating_button_position' => self::RAW,
        'floating_button_label' => self::TEXT,
        'categories' => self::CATEGORIES,
        'disabled_vendors' => self::IDS,
        'custom_vendors' => self::VENDORS,
        'allowed_patterns' => self::PATTERNS,
        'site_cookies' => self::SITE_COOKIES,
        'content_message_text' => self::HTML,
        'content_message_button_text' => self::TEXT,
        'excluded_pages' => self::TEXT,
    ];

    private const DEFAULTS = [
        'banner_bg' => '#fff',
        'banner_text_color' => '#000',
        'bannerStyle' => 'bannerStyle',
        'contentStyle' => 'contentStyle',
        'consentText' => 'consentText',
        'infoClass' => 'italybtn',
        'closeClass' => 'italybtn',
        'cookie_name' => Schema::COOKIE_NAME,
        'cookie_value' => Schema::COOKIE_VALUE,
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
            $value = $input[$name] ?? null;
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
            self::SITE_COOKIES => $this->siteCookies($value),
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

        $message = $name === 'cookie_name'
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
