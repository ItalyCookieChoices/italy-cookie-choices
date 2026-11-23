<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\UI;

use ItalyCookieChoices\Migration\Application\BuildingMigrationSources;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\UI\DismissedSources;
use ItalyCookieChoices\Migration\UI\MigrationAbilities;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyCookieChoices\Tests\Support\Migration\FakeMigrationSource;
use ItalyCookieChoices\Tests\Support\RunsAbilities;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class MigrationAbilitiesTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;
    use RunsAbilities;

    private const LIST = 'italy-cookie-choices/list-migration-sources';
    private const IMPORT = 'italy-cookie-choices/import-settings';
    private const RESULT = [
        'summary' => ['The texts of the banner and of the buttons'],
        'notes' => ['Check the colors.'],
    ];

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1', 'text' => 'Mine']);
        \add_action(BuildingMigrationSources::class, static function (BuildingMigrationSources $event): void {
            $event->addSource(new FakeMigrationSource(
                'other',
                new ImportedSettings(['text' => 'Imported'], [], ['Check the colors.'])
            ));
        });
    }

    public function testItRegistersWithTheAbilities(): void
    {
        $this->assertSame(
            ['wp_abilities_api_init' => 'register'],
            \iterator_to_array($this->service(MigrationAbilities::class)->getSubscribedEvents())
        );
    }

    public function testTheAdministratorsListThePluginsWithAPreview(): void
    {
        $this->logInAs('administrator');
        $ability = $this->ability(self::LIST);

        $this->assertContains(['id' => 'other', 'name' => 'Other', ...self::RESULT], (array) $ability->execute());
        $this->assertSame('Mine', $this->savedText());
        $this->assertTrue($this->annotations($ability)['readonly'] ?? null);
    }

    public function testTheAdministratorsImportThePluginSettings(): void
    {
        $this->logInAs('administrator');
        $ability = $this->ability(self::IMPORT);

        $this->assertSame(self::RESULT, $ability->execute(['source' => 'other']));

        $this->assertSame('Imported', $this->savedText());
        $this->assertSame(['other'], $this->service(DismissedSources::class)->all());
        $annotations = $this->annotations($ability);
        $this->assertFalse($annotations['readonly'] ?? null);
        $this->assertTrue($annotations['destructive'] ?? null);
        $this->assertTrue($annotations['idempotent'] ?? null);
    }

    public function testAnUnknownPluginIsAnError(): void
    {
        $this->logInAs('administrator');

        $error = $this->ability(self::IMPORT)->execute(['source' => 'unknown']);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertSame('italy_cookie_choices_unknown_migration', $error->get_error_code());
    }

    public function testTheOtherUsersCannotImport(): void
    {
        $this->logInAs('editor');

        $this->assertInstanceOf(\WP_Error::class, $this->ability(self::LIST)->execute());
        $this->assertInstanceOf(\WP_Error::class, $this->ability(self::IMPORT)->execute(['source' => 'other']));
    }

    private function savedText(): mixed
    {
        $saved = \get_option(Schema::OPTION);

        return \is_array($saved) ? $saved['text'] ?? null : null;
    }
}
