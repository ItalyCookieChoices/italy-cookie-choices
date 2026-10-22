<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

/**
 * Reads the saved settings when they are needed.
 */
final readonly class Options
{
    public function isActive(): bool
    {
        return $this->isChecked('active');
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
     * A saved text translated by WPML or Polylang, the default when it is missing or empty.
     *
     * @param string $name The name of the string registered for the translation.
     */
    public function translated(string $name, string $option, string $default = ''): string
    {
        $value = $this->text($option);
        if ($value === '') {
            return $default;
        }

        $translated = \get_string('Italy Cookie Choices', $name, $value);

        return $this->nonEmptyString($translated, $value);
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
            'name' => $this->nonEmptyString($options['cookie_name'] ?? null, Schema::COOKIE_NAME),
            'value' => $this->nonEmptyString($options['cookie_value'] ?? null, Schema::COOKIE_VALUE),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function all(): array
    {
        $options = \get_option(Schema::OPTION);

        return \is_array($options) ? $options : [];
    }

    private function nonEmptyString(mixed $value, string $default): string
    {
        return \is_string($value) && $value !== '' ? $value : $default;
    }
}
