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
