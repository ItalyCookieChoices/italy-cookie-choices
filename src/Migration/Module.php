<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration;

use ItalyCookieChoices\Console\Commands\CommandsConfigExtension;
use ItalyCookieChoices\Migration\Infrastructure\BuiltInSources;
use ItalyCookieChoices\Migration\UI\Cli\Commands\MigrateCommand;
use ItalyCookieChoices\Migration\UI\MigrationAbilities;
use ItalyCookieChoices\Migration\UI\MigrationEndpoint;
use ItalyCookieChoices\Migration\UI\MigrationNotice;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;

/**
 * The migration context: it imports the settings of the other consent plugins, from the settings page
 * and from WP-CLI.
 */
final readonly class Module implements ModuleInterface
{
    public function __invoke(): iterable
    {
        return [
            SubscribersConfigExtension::SUBSCRIBERS => [
                BuiltInSources::class,
                MigrationEndpoint::class,
                MigrationNotice::class,
                MigrationAbilities::class,
            ],
            CommandsConfigExtension::COMMANDS => [
                MigrateCommand::class,
            ],
        ];
    }
}
