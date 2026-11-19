<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Application;

/**
 * Reads the nested arrays of another plugin, its options and its JSON, without trusting their shape.
 */
final readonly class ArrayReader
{
    /**
     * The value at the path of keys, null when a key is missing.
     *
     * @param array<array-key, mixed> $data
     */
    public function value(array $data, string ...$keys): mixed
    {
        $value = $data;
        foreach ($keys as $key) {
            if (!\is_array($value) || !\array_key_exists($key, $value)) {
                return null;
            }

            $value = $value[$key];
        }

        return $value;
    }

    /**
     * The text at the path of keys, trimmed, empty when it is not a scalar.
     *
     * @param array<array-key, mixed> $data
     */
    public function text(array $data, string ...$keys): string
    {
        $value = $this->value($data, ...$keys);

        return \is_scalar($value) ? \trim((string) $value) : '';
    }

    /**
     * Whether the value at the path of keys is true, 1, "1" or "yes".
     *
     * @param array<array-key, mixed> $data
     */
    public function isOn(array $data, string ...$keys): bool
    {
        return \in_array($this->value($data, ...$keys), [true, 1, '1', 'yes', 'true'], true);
    }

    /**
     * The array at the path of keys, empty when it is not one.
     *
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    public function array(array $data, string ...$keys): array
    {
        $value = $this->value($data, ...$keys);

        return \is_array($value) ? $value : [];
    }
}
