<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cli;

use ItalyCookieChoices\Cli\Commands\CookieChoicesNamespace;
use ItalyCookieChoices\Cli\Commands\StatusCommand;
use ItalyCookieChoices\Console\Commands\CommandRegistry;
use ItalyCookieChoices\Console\Commands\CommandsConfigExtension;
use ItalyCookieChoices\Console\Commands\ConsoleCommandRegistry;
use ItalyCookieChoices\Console\IO\ConsoleIOFactory;
use ItalyCookieChoices\Console\IO\IOFactory;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;

/**
 * The WP-CLI commands of the plugin: `wp cookie-choices` and its status.
 * The commands of the log and of the settings are in the UI layer of their context.
 */
final readonly class Module implements ModuleInterface
{
    public function __invoke(): iterable
    {
        return [
            AurynConfig::ALIASES => [
                IOFactory::class => ConsoleIOFactory::class,
                CommandRegistry::class => ConsoleCommandRegistry::class,
            ],
            CommandsConfigExtension::COMMANDS => [
                'cookie-choices' => CookieChoicesNamespace::class,
                StatusCommand::class,
            ],
        ];
    }
}
