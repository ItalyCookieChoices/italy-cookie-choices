<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Cli;

use ItalyCookieChoices\Cli\Commands\StatusCommand;
use ItalyCookieChoices\Cli\Handlers\ShowStatus;
use ItalyCookieChoices\Cli\Commands\CookieChoicesNamespace;
use ItalyCookieChoices\Cli\Module;
use ItalyCookieChoices\Console\Commands\CommandRegistry;
use ItalyCookieChoices\Console\Commands\CommandsConfigExtension;
use ItalyCookieChoices\Console\Commands\ConsoleCommandRegistry;
use ItalyCookieChoices\Console\IO\ConsoleIOFactory;
use ItalyCookieChoices\Console\IO\IOFactory;
use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\FakeIO;
use ItalyCookieChoices\Tests\Support\FakeIOFactory;
use ItalyStrap\Empress\AurynConfig;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class StatusCommandTest extends WPTestCase
{
    use BuildsContainer;

    public function testTheModuleListsTheRootCommandAndTheStatus(): void
    {
        $config = (new Module())();

        $this->assertIsArray($config);
        $this->assertSame(
            ['cookie-choices' => CookieChoicesNamespace::class, StatusCommand::class],
            $config[CommandsConfigExtension::COMMANDS]
        );
        $this->assertSame(
            [IOFactory::class => ConsoleIOFactory::class, CommandRegistry::class => ConsoleCommandRegistry::class],
            $config[AurynConfig::ALIASES]
        );
        $this->assertSame('cookie-choices status', $this->service(StatusCommand::class)->name());
    }

    public function testItListsTheStatusOfTheSite(): void
    {
        \update_option(Schema::OPTION, [
            'active' => '1',
            'consent_mode' => '1',
            'consent_mode_advanced' => '1',
            'consent_log' => '1',
            'consent_log_retention' => '24',
            'disabled_vendors' => ['youtube'],
            'custom_vendors' => [
                ['id' => 'tracker', 'name' => 'Tracker', 'category' => 'statistics', 'patterns' => ['t.example.com']],
            ],
            'consent_duration' => '90',
        ]);

        $io = $this->runStatus();

        $this->assertSame(['setting', 'value'], $io->fields);
        $status = \array_column($io->items, 'value', 'setting');
        $this->assertSame('on', $status['banner'] ?? null);
        $this->assertSame('advanced', $status['consent_mode'] ?? null);
        $this->assertSame('off', $status['microsoft_uet'] ?? null);
        $this->assertSame('90', $status['consent_duration_days'] ?? null);
        $this->assertSame('on', $status['consent_log'] ?? null);
        $this->assertSame('24', $status['consent_log_retention_months'] ?? null);
        $this->assertSame('off', $status['uses_main_site_settings'] ?? null);
        $this->assertSame('', $status['cookie_domain'] ?? null);
        $blocked = \explode(', ', $status['blocked_vendors'] ?? '');
        $this->assertContains('vimeo', $blocked);
        $this->assertContains('tracker', $blocked);
        $this->assertNotContains('youtube', $blocked);
        $this->assertSame(0, $io->exit);
    }

    public function testTheConsentModeIsOffOrBasic(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);
        $this->assertContains(['setting' => 'consent_mode', 'value' => 'off'], $this->runStatus()->items);

        \update_option(Schema::OPTION, ['active' => '1', 'consent_mode' => '1']);
        $this->assertContains(['setting' => 'consent_mode', 'value' => 'basic'], $this->runStatus()->items);
    }

    private function runStatus(): FakeIO
    {
        $factory = new FakeIOFactory();
        $command = new StatusCommand(new CommandRunner(), $factory, $this->service(ShowStatus::class));

        $command([], ['format' => 'json']);

        return $factory->io();
    }
}
