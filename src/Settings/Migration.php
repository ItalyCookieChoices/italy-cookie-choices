<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

use ItalyStrap\Event\SubscriberInterface;

/**
 * Brings the stored option to the current schema version, one step at a time.
 *
 * It runs before the option is registered, so the values are stored as they are.
 */
final readonly class Migration implements SubscriberInterface
{
    public function __construct(
        private Schema $schema,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'plugins_loaded' => 'migrate';
    }

    public function migrate(): void
    {
        $storedVersion = $this->storedVersion();
        if ($storedVersion >= Schema::VERSION) {
            return;
        }

        foreach ($this->steps() as $version => $step) {
            if ($version <= $storedVersion) {
                continue;
            }

            $step();
        }

        \update_option(Schema::VERSION_OPTION, Schema::VERSION);
    }

    /**
     * @return array<int, callable(): void>
     */
    private function steps(): array
    {
        return [
            1 => $this->createTheDefaults(...),
        ];
    }

    /**
     * Version 1: the 2.x values are kept, a new installation gets the defaults.
     */
    private function createTheDefaults(): void
    {
        if (\get_option(Schema::OPTION, null) !== null) {
            return;
        }

        \add_option(Schema::OPTION, $this->schema->defaults());
    }

    private function storedVersion(): int
    {
        $version = \get_option(Schema::VERSION_OPTION, 0);

        return \is_numeric($version) ? (int) $version : 0;
    }
}
