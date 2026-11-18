<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Application;

use ItalyCookieChoices\Settings\Options;

/**
 * The consent per vendor: when the setting is on, the preferences let the visitors refuse a single vendor
 * inside an accepted category, or accept only some vendors of a category. Off, the consent is by category.
 */
final readonly class VendorChoices
{
    public const SETTING = 'vendor_consent';

    public function __construct(
        private VendorsFactory $factory,
        private VendorSettings $settings,
        private Options $options,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->options->isChecked(self::SETTING);
    }

    /**
     * The vendors that are blocked, with their names, empty when the consent is by category.
     *
     * @return list<array{id: string, name: string, category: string}>
     */
    public function all(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $names = \array_column($this->settings->all(), 'name', 'id');
        $vendors = [];
        foreach ($this->factory->create() as $vendor) {
            $vendors[] = [
                'id' => $vendor->id(),
                'name' => $names[$vendor->id()] ?? $this->nameOf($vendor->id()),
                'category' => $vendor->category(),
            ];
        }

        return $vendors;
    }

    /**
     * The name of a vendor that is not a preset nor a custom vendor, like the Google tag gateway.
     */
    private function nameOf(string $id): string
    {
        return \ucwords(\str_replace(['-', '_'], ' ', $id));
    }
}
