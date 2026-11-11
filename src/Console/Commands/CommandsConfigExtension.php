<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\Commands;

use ItalyStrap\Empress\AurynConfigInterface;
use ItalyStrap\Empress\Extension;
use ItalyStrap\Event\ListenerRegisterInterface;
use Psr\Container\ContainerInterface;

/**
 * Registers the commands the modules list under COMMANDS, like the subscribers of SubscribersConfigExtension.
 * A namespace, the parent of other commands, has its name as key:
 *
 * CommandsConfigExtension::COMMANDS => [
 *     'plugin log' => LogNamespace::class,
 *     LogExportCommand::class,
 * ],
 *
 * The commands are built and registered on cli_init only, the other requests do not load them.
 */
final class CommandsConfigExtension implements Extension
{
    public const COMMANDS = 'commands';
    public const EVENT = 'cli_init';

    private const PRIORITY = 10;

    /**
     * @var list<string>
     */
    private array $commands = [];

    /**
     * @var array<string, string>
     */
    private array $namespaces = [];

    public function __construct(
        private readonly ListenerRegisterInterface $listeners,
        private readonly CommandRegistry $registry,
        private readonly ContainerInterface $container,
    ) {
    }

    public function name(): string
    {
        return self::COMMANDS;
    }

    public function execute(AurynConfigInterface $application): void
    {
        $application->walk($this->name(), $this);
        if ($this->commands === [] && $this->namespaces === []) {
            return;
        }

        $this->listeners->addListener(self::EVENT, [$this, 'register'], self::PRIORITY, 0);
    }

    /**
     * Collects a command of the configuration, called by AurynConfig::walk() with the injector too.
     */
    public function __invoke(string $class, int|string $index): void
    {
        // Not loaded here: the class of a namespace extends a class of WP-CLI, missing on the other requests.
        if (\is_string($index)) {
            $this->namespaces[$index] = $class;
            return;
        }

        $this->commands[] = $class;
    }

    public function register(): void
    {
        foreach ($this->namespaces as $name => $class) {
            $this->registry->addNamespace($name, $class);
        }

        foreach ($this->commands as $class) {
            $command = $this->container->get($class);
            \assert($command instanceof CommandInterface);
            $this->registry->add($command);
        }
    }
}
