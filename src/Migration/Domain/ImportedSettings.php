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
     *                                   In a preview, only the ones that are off.
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

    /**
     * The same settings, with only these vendors in use.
     *
     * @param list<string> $vendors
     */
    public function withVendorsInUse(array $vendors): self
    {
        return new self($this->settings, $vendors, $this->notes);
    }

    /**
     * True when the import changes nothing: the notes alone are not something to import.
     */
    public function isEmpty(): bool
    {
        return $this->settings === [] && $this->vendorsInUse === [];
    }
}
