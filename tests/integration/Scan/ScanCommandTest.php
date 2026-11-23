<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Blocking\Application\VendorSettings;
use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Console\Commands\CommandsConfigExtension;
use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\Scan\Application\PageAnalyzer;
use ItalyCookieChoices\Scan\Application\ScanTargets;
use ItalyCookieChoices\Scan\Application\SiteScanner;
use ItalyCookieChoices\Scan\Module;
use ItalyCookieChoices\Scan\UI\Cli\Commands\ScanCommand;
use ItalyCookieChoices\Scan\UI\Cli\Handlers\ScanSite;
use ItalyCookieChoices\Scan\UI\Cli\Middlewares\ResolveTargets;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\FakeIO;
use ItalyCookieChoices\Tests\Support\FakeIOFactory;
use ItalyCookieChoices\Tests\Support\Scan\FakePageFetcher;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScanCommandTest extends WPTestCase
{
    use BuildsContainer;

    public function testTheModuleListsTheScanCommand(): void
    {
        $config = (new Module('/plugin/', 'https://example.com/plugin/', []))();

        $this->assertIsArray($config);
        $this->assertSame([ScanCommand::class], $config[CommandsConfigExtension::COMMANDS]);
        $this->assertSame('cookie-choices scan', $this->service(ScanCommand::class)->name());
    }

    public function testItScansTheSelectionAndListsTheVendorsAndTheHosts(): void
    {
        \update_option(Schema::OPTION, ['disabled_vendors' => ['hotjar']]);
        $page = $this->page();
        $other = $this->page();

        $io = $this->scan(['home' => true, 'post' => $page . ',' . $other], [
            \home_url('/') => '<script src="https://static.hotjar.com/c/hotjar.js"></script>',
            (string) \get_permalink($page) => '<iframe src="https://www.youtube.com/embed/a"></iframe>'
                . '<script src="https://static.hotjar.com/c/hotjar.js"></script>'
                . '<script src="https://cdn.tracker.example.org/t.js"></script>',
        ]);

        $this->assertSame(0, $io->exit);
        $this->assertSame(
            [
                $this->item('hotjar', 'Hotjar', 'statistics', 'off', '2'),
                $this->item('youtube', 'YouTube', 'marketing', 'on', '1'),
                $this->item('cdn.tracker.example.org', '', '', '', '1'),
            ],
            $io->items
        );
        $this->assertSame(['vendor', 'name', 'category', 'enabled', 'pages'], $io->fields);
        $this->assertContains('Scanned 3 of 3 pages.', $io->lines);
        $this->assertSame(
            ['The page ' . \get_permalink($other) . ' was not scanned: The page answered with the status 404.'],
            $io->errors
        );
    }

    public function testItNeedsSomethingToScan(): void
    {
        $io = $this->scan([], []);

        $this->assertSame(7, $io->exit);
        $this->assertSame(['There are no published pages to scan in the selection.'], $io->errors);
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, string> $pages
     */
    private function scan(array $options, array $pages): FakeIO
    {
        $factory = new FakeIOFactory();
        $command = new ScanCommand(
            new CommandRunner(),
            $factory,
            new ResolveTargets(new ScanTargets()),
            new ScanSite(
                new SiteScanner(
                    new ScanTargets(),
                    new FakePageFetcher($pages),
                    new PageAnalyzer(),
                    $this->service(VendorsFactory::class)
                ),
                $this->service(VendorSettings::class)
            )
        );

        $command([], $options);

        return $factory->io();
    }

    /**
     * @return array<string, string>
     */
    private function item(string $vendor, string $name, string $category, string $enabled, string $pages): array
    {
        return [
            'vendor' => $vendor,
            'name' => $name,
            'category' => $category,
            'enabled' => $enabled,
            'pages' => $pages,
        ];
    }

    private function page(): int
    {
        $page = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $this->assertIsInt($page);

        return $page;
    }
}
