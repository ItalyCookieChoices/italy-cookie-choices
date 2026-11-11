<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

/**
 * The file of the exported settings, the same of the settings page: {"plugin": ..., "version": 10, "settings": {...}}.
 *
 * It imports that file and the one of the 2.x versions, a list with the option, migrated from their version.
 */
final readonly class SettingsFile
{
    public const PLUGIN = 'italy-cookie-choices';

    /**
     * The 2.x files have no version, their option has the shape of the version 1.
     */
    private const LEGACY_VERSION = 1;

    public function __construct(
        private Migration $migration,
        private Options $options,
    ) {
    }

    /**
     * @return array{plugin: string, version: int, settings: array<array-key, mixed>}
     */
    public function export(): array
    {
        return [
            'plugin' => self::PLUGIN,
            'version' => Schema::VERSION,
            'settings' => $this->options->all(),
        ];
    }

    /**
     * Saves the settings of the file through the sanitizer.
     *
     * @param array<array-key, mixed> $file
     * @throws \InvalidArgumentException When the file has no settings, or they are of a newer version.
     */
    public function import(array $file): void
    {
        $version = $this->version($file);
        $settings = $this->settings($file);
        if ($settings === null || $version > Schema::VERSION) {
            throw new \InvalidArgumentException(
                \__('The file has no settings of Cookie Choices.', 'italy-cookie-choices')
            );
        }

        \update_option(Schema::OPTION, $this->migration->upgrade($settings, $version));
    }

    /**
     * @param array<array-key, mixed> $file
     */
    private function version(array $file): int
    {
        $version = $file['version'] ?? self::LEGACY_VERSION;

        return \is_int($version) ? $version : self::LEGACY_VERSION;
    }

    /**
     * @param array<array-key, mixed> $file
     * @return array<array-key, mixed>|null
     */
    private function settings(array $file): ?array
    {
        $settings = \array_is_list($file) ? $file[0] ?? null : $file['settings'] ?? null;

        return \is_array($settings) && $settings !== [] && !\array_is_list($settings) ? $settings : null;
    }
}
