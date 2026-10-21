<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

/**
 * Sanitizes the option saved from the settings page or the REST API, as the 2.x versions did.
 */
final readonly class Sanitizer
{
    private const RAW = 'raw';
    private const TEXT = 'text';
    private const HTML = 'html';
    private const TEXT_OR_DEFAULT = 'text_or_default';
    private const REQUIRED = 'required';

    /**
     * The fields in the order they are saved.
     */
    private const FIELDS = [
        'active' => self::RAW,
        'banner' => self::RAW,
        'scroll' => self::RAW,
        'secondView' => self::RAW,
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
        'block' => self::RAW,
        'widget_block' => self::RAW,
        'all_block' => self::RAW,
        'allow_iframe' => self::RAW,
        'allow_script' => self::RAW,
        'allow_embed' => self::RAW,
        'custom_script_block_body_exclude' => self::RAW,
        'block_iframe' => self::RAW,
        'block_script' => self::RAW,
        'block_embed' => self::RAW,
        'custom_script_block' => self::RAW,
        'content_message_text' => self::HTML,
        'content_message_button_text' => self::TEXT,
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

    /**
     * The strings registered for WPML and Polylang, with their label.
     */
    private const TRANSLATABLE = [
        'text' => 'Banner text',
        'url' => 'Banner url',
        'slug' => 'Banner slug',
        'anchor_text' => 'Banner anchor text',
        'button_text' => 'Banner button text',
        'content_message_text' => 'Content message text',
        'content_message_button_text' => 'Content message button text',
    ];

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
            $this->registerTranslatable($name, $sanitized[$name]);
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

    private function registerTranslatable(string $name, mixed $value): void
    {
        if (!\array_key_exists($name, self::TRANSLATABLE) || !\is_string($value)) {
            return;
        }

        \register_string('Italy Cookie Choices', self::TRANSLATABLE[$name], $value);
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
