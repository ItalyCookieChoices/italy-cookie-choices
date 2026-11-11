<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Console\Commands;

use ItalyCookieChoices\Console\Commands\CommandsConfigExtension;
use ItalyCookieChoices\Tests\Support\Console\CommandContainer;
use ItalyCookieChoices\Tests\Support\Console\CommandRegistrySpy;
use ItalyCookieChoices\Tests\Support\Console\ExampleCommand;
use ItalyStrap\Empress\ContainerBuilder;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CommandsConfigExtensionTest extends WPTestCase
{
    public function testTheCommandsOfTheModulesAreRegisteredOnCliInit(): void
    {
        $registry = new CommandRegistrySpy();
        $extension = new CommandsConfigExtension(
            new GlobalOrderedListenerProvider(),
            $registry,
            new CommandContainer()
        );

        (new ContainerBuilder())
            ->addModule($this->module(['example' => 'Example\\NotLoadedNamespace', ExampleCommand::class]))
            ->extend($extension)
            ->build();

        $this->assertSame(10, \has_action(CommandsConfigExtension::EVENT, [$extension, 'register']));
        $this->assertSame([], $registry->names);

        $extension->register();

        $this->assertSame(['namespace example Example\\NotLoadedNamespace', 'example run'], $registry->names);
    }

    public function testWithoutCommandsNothingIsHooked(): void
    {
        $extension = new CommandsConfigExtension(
            new GlobalOrderedListenerProvider(),
            new CommandRegistrySpy(),
            new CommandContainer()
        );

        (new ContainerBuilder())
            ->addModule($this->module([]))
            ->extend($extension)
            ->build();

        $this->assertFalse(\has_action(CommandsConfigExtension::EVENT, [$extension, 'register']));
    }

    /**
     * @param array<array-key, string> $commands
     */
    private function module(array $commands): ModuleInterface
    {
        return new class ($commands) implements ModuleInterface {
            /**
             * @param array<array-key, string> $commands
             */
            public function __construct(
                private readonly array $commands,
            ) {
            }

            public function __invoke(): iterable
            {
                return [CommandsConfigExtension::COMMANDS => $this->commands];
            }
        };
    }
}
