<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Domain;

/**
 * Another consent plugin whose settings Cookie Choices imports.
 *
 * A source reads the settings from the database, so it works also when the other plugin is deactivated.
 * Add one with the BuildingMigrationSources event.
 */
interface MigrationSource
{
    /**
     * A unique id, like "complianz", used in the REST API and in WP-CLI.
     */
    public function id(): string;

    /**
     * The name of the plugin, shown to the admin.
     */
    public function name(): string;

    /**
     * Whether the settings of the plugin are in the database.
     */
    public function isFound(): bool;

    /**
     * The settings of the plugin in the shape of the option of Cookie Choices.
     */
    public function read(): ImportedSettings;
}
