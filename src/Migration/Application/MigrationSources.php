<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Application;

use ItalyCookieChoices\Migration\Domain\MigrationSource;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The plugins Cookie Choices imports from, the built-in ones and the ones of BuildingMigrationSources.
 */
final readonly class MigrationSources
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * The sources whose settings are in the database, with something to import.
     *
     * @return list<MigrationSource>
     */
    public function found(): array
    {
        return \array_values(\array_filter(
            $this->all(),
            static fn(MigrationSource $source): bool => $source->isFound() && !$source->read()->isEmpty()
        ));
    }

    /**
     * @throws \InvalidArgumentException When the source is not found.
     */
    public function get(string $id): MigrationSource
    {
        foreach ($this->found() as $source) {
            if ($source->id() === $id) {
                return $source;
            }
        }

        throw new \InvalidArgumentException(
            \__('There are no settings of this plugin to import.', 'italy-cookie-choices')
        );
    }

    /**
     * @return list<MigrationSource>
     */
    private function all(): array
    {
        $event = new BuildingMigrationSources();
        $this->dispatcher->dispatch($event);

        return $event->sources();
    }
}
