<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Application;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Cookies\Application\CookieSetting;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Settings\Options;

/**
 * Imports the settings of another plugin into the option of Cookie Choices.
 *
 * The imported settings replace the saved ones, the others stay. The lists keep their entries and get the new
 * ones, the categories keep the texts the import has not, the presets in use are turned on.
 * The option is saved through its sanitizer.
 */
final readonly class Migrate
{
    /**
     * The lists, with the field that tells two entries apart, null for the lists of texts.
     */
    private const LISTS = [
        BlockingSetting::CUSTOM_VENDORS => 'id',
        BlockingSetting::CUSTOM_SCRIPTS => 'code',
        BlockingSetting::ALLOWED_PATTERNS => null,
        CookieSetting::SITE_COOKIES => 'name',
    ];

    public function __construct(
        private MigrationSources $sources,
        private Options $options,
    ) {
    }

    /**
     * What the import of a source saves, without saving it.
     *
     * @throws \InvalidArgumentException When the source is not found.
     */
    public function preview(string $source): ImportedSettings
    {
        $imported = $this->sources->get($source)->read();
        $disabled = $this->disabled($this->options->all()[BlockingSetting::DISABLED_VENDORS] ?? []);

        return $imported->withVendorsInUse(\array_values(\array_intersect($imported->vendorsInUse(), $disabled)));
    }

    /**
     * @throws \InvalidArgumentException When the source is not found.
     */
    public function import(string $source): ImportedSettings
    {
        $imported = $this->preview($source);
        $saved = $this->options->all();
        $merged = [];
        foreach ($imported->settings() as $name => $value) {
            $merged[$name] = $this->merged($name, $saved[$name] ?? null, $value);
        }

        $merged[BlockingSetting::DISABLED_VENDORS] = $this->disabledVendors(
            $saved[BlockingSetting::DISABLED_VENDORS] ?? [],
            $imported->vendorsInUse()
        );
        $this->options->save($merged);

        return $imported;
    }

    private function merged(string $name, mixed $saved, mixed $imported): mixed
    {
        if (!\is_array($saved) || !\is_array($imported)) {
            return $imported;
        }

        if ($name === ConsentSetting::CATEGORIES) {
            return [...$saved, ...$imported];
        }

        if (!\array_key_exists($name, self::LISTS)) {
            return $imported;
        }

        return $this->appended(\array_values($saved), \array_values($imported), self::LISTS[$name]);
    }

    /**
     * @param list<mixed> $saved
     * @param list<mixed> $imported
     * @return list<mixed>
     */
    private function appended(array $saved, array $imported, ?string $field): array
    {
        $keys = \array_map(fn(mixed $entry): mixed => $this->keyOf($entry, $field), $saved);
        foreach ($imported as $entry) {
            $key = $this->keyOf($entry, $field);
            if (\in_array($key, $keys, true)) {
                continue;
            }

            $saved[] = $entry;
            $keys[] = $key;
        }

        return $saved;
    }

    private function keyOf(mixed $entry, ?string $field): mixed
    {
        return $field !== null && \is_array($entry) ? $entry[$field] ?? null : $entry;
    }

    /**
     * @param list<string> $inUse
     * @return list<string>
     */
    private function disabledVendors(mixed $disabled, array $inUse): array
    {
        return \array_values(\array_diff($this->disabled($disabled), $inUse));
    }

    /**
     * @return list<string>
     */
    private function disabled(mixed $disabled): array
    {
        return \array_values(\array_filter(\is_array($disabled) ? $disabled : [], \is_string(...)));
    }
}
