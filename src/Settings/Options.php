<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Consent\Application\ConsentSetting;

/**
 * Reads the saved settings when they are needed.
 */
final readonly class Options
{
    /**
     * The texts translated with WPML and Polylang, with the name of their string.
     */
    public const TRANSLATABLE = [
        ConsentSetting::TEXT => 'Banner text',
        ConsentSetting::POLICY_URL => 'Banner url',
        ConsentSetting::POLICY_TEXT => 'Banner anchor text',
        ConsentSetting::ACCEPT_BUTTON_TEXT => 'Banner button text',
        ConsentSetting::REJECT_BUTTON_TEXT => 'Banner reject button text',
        ConsentSetting::PREFERENCES_BUTTON_TEXT => 'Banner preferences button text',
        ConsentSetting::SAVE_BUTTON_TEXT => 'Preferences save button text',
        ConsentSetting::LEGAL_NOTICE_URL => 'Banner legal notice url',
        ConsentSetting::LEGAL_NOTICE_TEXT => 'Banner legal notice text',
        BlockingSetting::PLACEHOLDER_TEXT => 'Content message text',
        BlockingSetting::PLACEHOLDER_BUTTON_TEXT => 'Content message button text',
        ConsentSetting::FLOATING_BUTTON_LABEL => 'Floating button label',
    ];

    /**
     * The translatable texts with more lines, they get a textarea in the multilingual plugins.
     */
    public const MULTILINE = [ConsentSetting::TEXT, BlockingSetting::PLACEHOLDER_TEXT];

    public function __construct(
        private StringTranslations $translations,
    ) {
    }

    public function isActive(): bool
    {
        return $this->isChecked(PluginSetting::ACTIVE);
    }

    /**
     * A checkbox is on when its key is saved.
     */
    public function isChecked(string $name): bool
    {
        return \array_key_exists($name, $this->all());
    }

    /**
     * A saved text, the default when it is missing or empty.
     */
    public function text(string $name, string $default = ''): string
    {
        return $this->nonEmptyString($this->all()[$name] ?? null, $default);
    }

    /**
     * A saved text of TRANSLATABLE, translated, the default when it is missing or empty.
     */
    public function translated(string $option, string $default = ''): string
    {
        $value = $this->text($option);
        if ($value === '') {
            return $default;
        }

        return $this->nonEmptyString(
            $this->translations->translate(self::TRANSLATABLE[$option] ?? $option, $value),
            $value
        );
    }

    /**
     * The saved label or description of a category, translated, the default when it is missing or empty.
     *
     * @param 'label'|'description' $field
     */
    public function translatedCategoryText(string $category, string $field, string $default = ''): string
    {
        $value = $this->categoryTexts()[$category][$field] ?? '';
        if ($value === '') {
            return $default;
        }

        return $this->nonEmptyString(
            $this->translations->translate($this->categoryStringName($category, $field), $value),
            $value
        );
    }

    /**
     * The name of the string of a category text in the multilingual plugins.
     */
    public function categoryStringName(string $category, string $field): string
    {
        return \sprintf('Category %s %s', $category, $field);
    }

    /**
     * The saved texts of the categories, keyed by category id and by field.
     *
     * @return array<string, array<string, string>>
     */
    public function categoryTexts(): array
    {
        $categories = $this->all()['categories'] ?? [];
        $texts = [];
        foreach (\is_array($categories) ? $categories : [] as $id => $fields) {
            $texts[(string) $id] = $this->nonEmptyStrings(\is_array($fields) ? $fields : []);
        }

        return $texts;
    }

    /**
     * @param array<array-key, mixed> $values
     * @return array<string, string>
     */
    private function nonEmptyStrings(array $values): array
    {
        $strings = [];
        foreach ($values as $key => $value) {
            if (!\is_string($value) || $value === '') {
                continue;
            }

            $strings[(string) $key] = $value;
        }

        return $strings;
    }

    /**
     * The arrays of a saved list, like the custom vendors.
     *
     * @return list<array<array-key, mixed>>
     */
    public function records(string $name): array
    {
        $records = $this->all()[$name] ?? [];

        return \array_values(\array_filter(\is_array($records) ? $records : [], \is_array(...)));
    }

    /**
     * The non empty texts of a saved list.
     *
     * @return list<string>
     */
    public function values(string $name): array
    {
        $values = $this->all()[$name] ?? [];
        if (!\is_array($values)) {
            return [];
        }

        return \array_values(\array_filter(
            $values,
            static fn(mixed $value): bool => \is_string($value) && \trim($value) !== ''
        ));
    }

    /**
     * The cookie the 2.x banner writes on accept.
     *
     * @return array{name: string, value: string}
     */
    public function legacyCookie(): array
    {
        $options = $this->all();

        return [
            'name' => $this->nonEmptyString(
                $options[PluginSetting::LEGACY_COOKIE_NAME] ?? null,
                Schema::COOKIE_NAME
            ),
            'value' => $this->nonEmptyString(
                $options[PluginSetting::LEGACY_COOKIE_VALUE] ?? null,
                Schema::COOKIE_VALUE
            ),
        ];
    }

    /**
     * The saved settings, the defaults of the Schema before the first save.
     *
     * @return array<array-key, mixed>
     */
    public function all(): array
    {
        $options = \get_option(Schema::OPTION);

        return \is_array($options) ? $options : [];
    }

    /**
     * Saves the values over the saved settings, the Sanitizer of the option runs on the whole option.
     *
     * @param array<string, mixed> $values
     */
    public function save(array $values): void
    {
        \update_option(Schema::OPTION, [...$this->all(), ...$values]);
    }

    private function nonEmptyString(mixed $value, string $default): string
    {
        return \is_string($value) && $value !== '' ? $value : $default;
    }
}
