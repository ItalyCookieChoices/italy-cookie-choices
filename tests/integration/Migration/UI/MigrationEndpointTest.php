<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\UI;

use ItalyCookieChoices\Migration\Application\BuildingMigrationSources;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\UI\DismissedSources;
use ItalyCookieChoices\Migration\UI\MigrationEndpoint;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyCookieChoices\Tests\Support\Migration\FakeMigrationSource;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class MigrationEndpointTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;

    public function setUp(): void
    {
        parent::setUp();
        \add_action(BuildingMigrationSources::class, static function (BuildingMigrationSources $event): void {
            $event->addSource(new FakeMigrationSource(
                'other',
                new ImportedSettings(['text' => 'Imported', 'site_cookies' => [
                    ['name' => 'a', 'category' => 'necessary', 'duration' => '', 'purpose' => ''],
                ]], [], ['Check the colors.'])
            ));
        });
        global $wp_rest_server;
        $wp_rest_server = new \WP_REST_Server();
        \remove_all_actions('rest_api_init');
        \add_action('rest_api_init', [$this->service(MigrationEndpoint::class), 'register']);
        \do_action('rest_api_init', $wp_rest_server);
    }

    public function tearDown(): void
    {
        global $wp_rest_server;
        $wp_rest_server = null;
        parent::tearDown();
    }

    public function testThePreviewSaysWhatChangesAndWhatToCheck(): void
    {
        $this->logInAs('administrator');
        \update_option(Schema::OPTION, ['text' => 'Mine']);

        $response = \rest_do_request(new \WP_REST_Request('GET', '/italy-cookie-choices/v1/migration/other'));

        $this->assertSame(200, $response->get_status());
        $this->assertSame(
            [
                'summary' => ['The texts of the banner and of the buttons', 'Site cookies: 1'],
                'notes' => ['Check the colors.'],
            ],
            $response->get_data()
        );
        $this->assertSame('Mine', $this->savedText());
    }

    public function testTheImportSavesTheSettingsAndDismissesTheNotice(): void
    {
        $this->logInAs('administrator');

        $response = \rest_do_request(new \WP_REST_Request('POST', '/italy-cookie-choices/v1/migration/other'));

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame('Imported', \is_array($data['settings'] ?? null) ? $data['settings']['text'] ?? null : null);
        $this->assertSame(['other'], $this->service(DismissedSources::class)->all());
    }

    public function testAnUnknownPluginIsNotFound(): void
    {
        $this->logInAs('administrator');

        $response = \rest_do_request(new \WP_REST_Request('POST', '/italy-cookie-choices/v1/migration/unknown'));

        $this->assertSame(404, $response->get_status());
    }

    public function testOnlyTheAdministratorsImport(): void
    {
        $this->logInAs('editor');

        $response = \rest_do_request(new \WP_REST_Request('POST', '/italy-cookie-choices/v1/migration/other'));

        $this->assertSame(403, $response->get_status());
        $this->assertNotSame('Imported', $this->savedText());
    }

    private function savedText(): mixed
    {
        $option = \get_option(Schema::OPTION);

        return \is_array($option) ? $option['text'] ?? null : null;
    }
}
