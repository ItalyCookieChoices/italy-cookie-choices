<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Application;

use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Settings\Options;

/**
 * The vendors as the site owner sets them: the presets turned on or off and the custom vendors.
 */
final readonly class VendorSettings
{
    private const DISABLED = 'disabled_vendors';
    private const CUSTOM = 'custom_vendors';

    public function __construct(
        private VendorPresets $presets,
        private Options $options,
        private ConsentPolicyFactory $policy,
    ) {
    }

    /**
     * The presets first, then the custom vendors, always on.
     *
     * @return list<array{id: string, name: string, category: string, preset: bool, enabled: bool}>
     */
    public function all(): array
    {
        $disabled = $this->options->values(self::DISABLED);
        $vendors = [];
        foreach ($this->presets->all() as $vendor) {
            $vendors[] = [
                'id' => $vendor->id(),
                'name' => $this->presets->name($vendor->id()),
                'category' => $vendor->category(),
                'preset' => true,
                'enabled' => !\in_array($vendor->id(), $disabled, true),
            ];
        }

        foreach ($this->customVendors() as $vendor) {
            $vendors[] = [
                'id' => $vendor['id'],
                'name' => $vendor['name'],
                'category' => $vendor['category'],
                'preset' => false,
                'enabled' => true,
            ];
        }

        return $vendors;
    }

    /**
     * @throws \InvalidArgumentException When the id is not a preset.
     */
    public function enable(string $id): void
    {
        $this->assertIsAPreset($id);
        $this->options->save([self::DISABLED => $this->disabledWithout($id)]);
    }

    /**
     * @throws \InvalidArgumentException When the id is not a preset.
     */
    public function disable(string $id): void
    {
        $this->assertIsAPreset($id);
        $this->options->save([self::DISABLED => [...$this->disabledWithout($id), $id]]);
    }

    /**
     * Adds a custom vendor, its id is made from its name like on the settings page.
     *
     * @param string $category A category the visitors can refuse.
     * @param list<string> $patterns The texts of the addresses and of the inline scripts to block.
     * @return array<array-key, mixed> The custom vendor as it is saved.
     * @throws \InvalidArgumentException When the vendor is not valid.
     */
    public function add(string $name, string $category, array $patterns): array
    {
        $name = \trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException(\__('The vendor needs a name.', 'italy-cookie-choices'));
        }

        $id = \sanitize_key($name);
        $this->assertIsNew($id);
        $this->assertIsOptional($category);
        $patterns = $this->nonEmpty($patterns);
        if ($patterns === []) {
            throw new \InvalidArgumentException(
                \__('The vendor needs at least a pattern to block.', 'italy-cookie-choices')
            );
        }

        // Throws when the id is not valid.
        new Vendor($id, $category, $patterns, $patterns);

        $custom = $this->options->records(self::CUSTOM);
        $custom[] = ['id' => $id, 'name' => $name, 'category' => $category, 'patterns' => $patterns];
        $this->options->save([self::CUSTOM => $custom]);

        return $this->options->records(self::CUSTOM)[\count($custom) - 1] ?? [];
    }

    private function assertIsAPreset(string $id): void
    {
        foreach ($this->presets->all() as $vendor) {
            if ($vendor->id() === $id) {
                return;
            }
        }

        throw new \InvalidArgumentException(\sprintf(
            /* translators: %s: the id of the vendor. */
            \__('There is no common vendor with the id "%s".', 'italy-cookie-choices'),
            $id
        ));
    }

    private function assertIsNew(string $id): void
    {
        $taken = \array_column($this->all(), 'id');
        if (!\in_array($id, $taken, true)) {
            return;
        }

        throw new \InvalidArgumentException(\sprintf(
            /* translators: %s: the id of the vendor. */
            \__('A vendor with the id "%s" already exists.', 'italy-cookie-choices'),
            $id
        ));
    }

    private function assertIsOptional(string $category): void
    {
        $optional = $this->policy->create()->categories()->optionalIds();
        if (\in_array($category, $optional, true)) {
            return;
        }

        throw new \InvalidArgumentException(\sprintf(
            /* translators: 1: the id of the category, 2: the ids of the categories, separated by commas. */
            \__('The category "%1$s" is not one the visitors can refuse: %2$s.', 'italy-cookie-choices'),
            $category,
            \implode(', ', $optional)
        ));
    }

    /**
     * @return list<string>
     */
    private function disabledWithout(string $id): array
    {
        return \array_values(\array_diff($this->options->values(self::DISABLED), [$id]));
    }

    /**
     * @return list<array{id: string, name: string, category: string}>
     */
    private function customVendors(): array
    {
        $vendors = [];
        foreach ($this->options->records(self::CUSTOM) as $record) {
            $vendors[] = [
                'id' => \is_string($record['id'] ?? null) ? $record['id'] : '',
                'name' => \is_string($record['name'] ?? null) ? $record['name'] : '',
                'category' => \is_string($record['category'] ?? null) ? $record['category'] : '',
            ];
        }

        return $vendors;
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private function nonEmpty(array $values): array
    {
        return \array_values(\array_filter(
            \array_map('trim', $values),
            static fn(string $value): bool => $value !== ''
        ));
    }
}
