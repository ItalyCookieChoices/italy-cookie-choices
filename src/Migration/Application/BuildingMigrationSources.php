<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Application;

use ItalyCookieChoices\Migration\Domain\MigrationSource;

/**
 * Dispatched when the migration sources are collected, a plugin adds its own:
 *
 *     add_action( BuildingMigrationSources::class, static function ( $event ): void {
 *         $event->addSource( new MyPluginSource() );
 *     } );
 */
final class BuildingMigrationSources
{
    /**
     * @var array<string, MigrationSource>
     */
    private array $sources = [];

    public function addSource(MigrationSource $source): void
    {
        $this->sources[$source->id()] = $source;
    }

    /**
     * @return list<MigrationSource>
     */
    public function sources(): array
    {
        return \array_values($this->sources);
    }
}
