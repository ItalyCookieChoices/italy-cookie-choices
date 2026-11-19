<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\UI;

use ItalyCookieChoices\Migration\Application\BuildingMigrationSources;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\UI\DismissedSources;
use ItalyCookieChoices\Migration\UI\MigrationNotice;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyCookieChoices\Tests\Support\Migration\FakeMigrationSource;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class MigrationNoticeTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;

    public function setUp(): void
    {
        parent::setUp();
        \add_action(BuildingMigrationSources::class, static function (BuildingMigrationSources $event): void {
            $event->addSource(new FakeMigrationSource('other', new ImportedSettings([])));
        });
    }

    public function testItShowsTheNoticeAndDismissesIt(): void
    {
        $this->assertSame(
            ['admin_notices' => 'show', 'admin_post_italy_cookie_choices_dismiss_migration' => 'dismiss'],
            \iterator_to_array($this->service(MigrationNotice::class)->getSubscribedEvents())
        );
    }

    public function testTheAdministratorsSeeThePluginsToImport(): void
    {
        $this->logInAs('administrator');

        $html = $this->shown();

        $this->assertStringContainsString('Cookie Choices can import the settings of Other.', $html);
        $this->assertStringContainsString('page=italy-cookie-choices&#038;tab=tools', $html);
        $this->assertStringContainsString('action=italy_cookie_choices_dismiss_migration', $html);
    }

    public function testNothingIsShownForADismissedPlugin(): void
    {
        $this->logInAs('administrator');
        $this->service(DismissedSources::class)->add('other');

        $this->assertSame('', $this->shown());
    }

    public function testNothingIsShownToTheEditors(): void
    {
        $this->logInAs('editor');

        $this->assertSame('', $this->shown());
    }

    private function shown(): string
    {
        \ob_start();
        $this->service(MigrationNotice::class)->show();

        return (string) \ob_get_clean();
    }
}
