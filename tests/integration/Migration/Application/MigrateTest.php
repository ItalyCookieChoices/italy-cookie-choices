<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Application;

use ItalyCookieChoices\Migration\Application\BuildingMigrationSources;
use ItalyCookieChoices\Migration\Application\Migrate;
use ItalyCookieChoices\Migration\Application\MigrationSources;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyCookieChoices\Tests\Support\Migration\FakeMigrationSource;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class MigrateTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;

    private ImportedSettings $imported;

    public function setUp(): void
    {
        parent::setUp();
        $this->logInAs('administrator');
        $this->imported = new ImportedSettings(
            [
                'text' => 'Imported text',
                'categories' => ['marketing' => ['label' => 'Ads', 'description' => 'Imported']],
                'custom_vendors' => [
                    ['id' => 'chat', 'name' => 'Chat', 'category' => 'preferences', 'patterns' => ['chat.example.com']],
                    ['id' => 'ads', 'name' => 'Ads', 'category' => 'marketing', 'patterns' => ['ads.example.com']],
                ],
                'allowed_patterns' => ['youtube.com/embed/public', 'maps.example.com'],
            ],
            ['google-recaptcha'],
            ['The regions are not imported.']
        );
        \add_action(BuildingMigrationSources::class, function (BuildingMigrationSources $event): void {
            $event->addSource(new FakeMigrationSource('other', $this->imported));
            $event->addSource(new FakeMigrationSource('missing', $this->imported, false));
            $event->addSource(new FakeMigrationSource('empty', new ImportedSettings([], [], ['A note.'])));
        });
    }

    public function testTheSourcesAreTheOnesFoundInTheDatabase(): void
    {
        $ids = \array_map(
            static fn($source): string => $source->id(),
            $this->service(MigrationSources::class)->found()
        );

        $this->assertContains('other', $ids);
        $this->assertNotContains('missing', $ids);
    }

    public function testASourceWithNothingToImportIsNotFound(): void
    {
        $ids = \array_map(
            static fn($source): string => $source->id(),
            $this->service(MigrationSources::class)->found()
        );

        $this->assertNotContains('empty', $ids);
        $this->expectException(\InvalidArgumentException::class);
        $this->service(Migrate::class)->import('empty');
    }

    public function testThePreviewChangesNothing(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'text' => 'Mine']);

        $imported = $this->service(Migrate::class)->preview('other');

        $this->assertSame($this->imported->settings(), $imported->settings());
        $this->assertSame($this->imported->notes(), $imported->notes());
        $this->assertSame('Mine', $this->option()['text'] ?? null);
    }

    public function testTheVendorsInUseAreOnlyTheOnesTheImportTurnsOn(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'disabled_vendors' => ['hotjar']]);

        $this->assertSame([], $this->service(Migrate::class)->preview('other')->vendorsInUse());

        \update_option(Schema::OPTION, ['active' => '1', 'disabled_vendors' => ['google-recaptcha', 'hotjar']]);

        $this->assertSame(['google-recaptcha'], $this->service(Migrate::class)->preview('other')->vendorsInUse());
        $this->assertSame(['google-recaptcha'], $this->service(Migrate::class)->import('other')->vendorsInUse());
        $this->assertSame(['hotjar'], $this->option()['disabled_vendors'] ?? null);
    }

    public function testTheImportOverwritesTheImportedSettingsAndKeepsTheOthers(): void
    {
        \update_option(Schema::OPTION, [
            'active' => '1',
            'text' => 'Mine',
            'reload' => '1',
            'categories' => ['statistics' => ['label' => 'Analytics', 'description' => 'Mine']],
            'custom_vendors' => [
                ['id' => 'chat', 'name' => 'My chat', 'category' => 'statistics', 'patterns' => ['chat.example.com']],
            ],
            'allowed_patterns' => ['youtube.com/embed/public'],
            'disabled_vendors' => ['google-recaptcha', 'hotjar'],
        ]);

        $this->service(Migrate::class)->import('other');

        $option = $this->option();
        $this->assertSame('Imported text', $option['text'] ?? null);
        $this->assertSame('1', $option['reload'] ?? null);
        $this->assertSame(
            [
                'statistics' => ['label' => 'Analytics', 'description' => 'Mine'],
                'marketing' => ['label' => 'Ads', 'description' => 'Imported'],
            ],
            $option['categories'] ?? null
        );
        $vendors = $option['custom_vendors'] ?? null;
        $this->assertIsArray($vendors);
        $this->assertSame(['chat', 'ads'], \array_column($vendors, 'id'));
        $this->assertSame('My chat', \array_column($vendors, 'name')[0] ?? null);
        $this->assertSame(['youtube.com/embed/public', 'maps.example.com'], $option['allowed_patterns'] ?? null);
        $this->assertSame(['hotjar'], $option['disabled_vendors'] ?? null);
    }

    public function testAnUnknownSourceCannotBeImported(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service(Migrate::class)->import('missing');
    }

    /**
     * @return array<array-key, mixed>
     */
    private function option(): array
    {
        $option = \get_option(Schema::OPTION);
        $this->assertIsArray($option);

        return $option;
    }
}
