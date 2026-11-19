<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Domain;

/**
 * The settings read from another plugin, with what could not be imported.
 */
final readonly class ImportedSettings
{
    /**
     * @param array<string, mixed> $settings The settings in the shape of the option of Cookie Choices.
     * @param list<string> $vendorsInUse The ids of the presets the site uses, turned on by the import.
     * @param list<string> $notes What was not imported, or needs a check, in the language of the admin.
     */
    public function __construct(
        private array $settings,
        private array $vendorsInUse = [],
        private array $notes = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return $this->settings;
    }

    /**
     * @return list<string>
     */
    public function vendorsInUse(): array
    {
        return $this->vendorsInUse;
    }

    /**
     * @return list<string>
     */
    public function notes(): array
    {
        return $this->notes;
    }
}
