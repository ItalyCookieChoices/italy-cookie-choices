<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Admin;

use Italy_Cookie_Choices\Admin\Settings;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ImportExportTest extends WPTestCase
{
    private const OPTION_NAME = 'italy_cookie_choices';

    private array $post = [];

    private array $files = [];

    private string $importFile = '';

    public function setUp(): void
    {
        parent::setUp();
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->importFile = (string) \tempnam(\sys_get_temp_dir(), 'icc');
        \wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
    }

    public function tearDown(): void
    {
        $_POST = $this->post;
        $_FILES = $this->files;
        \unlink($this->importFile);
        parent::tearDown();
    }

    public function testExportDoesNothingWithoutTheAction(): void
    {
        $_POST = [];

        $this->assertNull($this->export());
    }

    public function testExportDoesNothingForOtherActions(): void
    {
        $_POST = ['icc_action' => 'import_settings', 'icc_export_nonce' => \wp_create_nonce('icc_export_nonce')];

        $this->assertNull($this->export());
    }

    public function testExportDoesNothingWithAnInvalidNonce(): void
    {
        $_POST = ['icc_action' => 'export_settings', 'icc_export_nonce' => 'invalid'];

        $this->assertNull($this->export());
    }

    public function testExportDoesNothingWithoutCapability(): void
    {
        \wp_set_current_user(self::factory()->user->create(['role' => 'editor']));
        $_POST = ['icc_action' => 'export_settings', 'icc_export_nonce' => \wp_create_nonce('icc_export_nonce')];

        $this->assertNull($this->export());
    }

    public function testImportDoesNothingWithoutTheAction(): void
    {
        $_POST = [];
        \update_option(self::OPTION_NAME, ['active' => '1']);

        $this->assertNull((new Settings())->settings_import());
        $this->assertSame(['active' => '1'], \get_option(self::OPTION_NAME));
    }

    public function testImportDoesNothingWithAnInvalidNonce(): void
    {
        $this->prepareImport('settings.json', '[{"active":"0"}]');
        $_POST['icc_import_nonce'] = 'invalid';
        \update_option(self::OPTION_NAME, ['active' => '1']);

        $this->assertNull((new Settings())->settings_import());
        $this->assertSame(['active' => '1'], \get_option(self::OPTION_NAME));
    }

    public function testImportDoesNothingWithoutCapability(): void
    {
        \wp_set_current_user(self::factory()->user->create(['role' => 'editor']));
        $this->prepareImport('settings.json', '[{"active":"0"}]');
        \update_option(self::OPTION_NAME, ['active' => '1']);

        $this->assertNull((new Settings())->settings_import());
        $this->assertSame(['active' => '1'], \get_option(self::OPTION_NAME));
    }

    public function testImportRejectsFilesThatAreNotJson(): void
    {
        $this->prepareImport('settings.txt', '[{"active":"0"}]');
        $this->captureDie();

        $this->assertSame(
            ['Please upload a valid .json file', 'No valid json file', ['back_link' => true]],
            $this->die()
        );
    }

    public function testImportRejectsEmptyFiles(): void
    {
        $this->prepareImport('settings.json', '');
        $this->captureDie();

        $this->assertSame(
            ["The json file can't be empty", 'Empty file', ['back_link' => true]],
            $this->die()
        );
    }

    public function testImportRejectsMissingUploads(): void
    {
        $this->prepareImport('settings.json', '[{"active":"0"}]');
        $_FILES['icc_import_file']['tmp_name'] = '';
        $this->captureDie();

        $this->assertSame(
            ['Please upload a file to import', 'No file import', ['back_link' => true]],
            $this->die()
        );
    }

    public function testImportSavesTheSettingsAndRedirectsToTheSettingsPage(): void
    {
        $this->prepareImport('export.settings.json', '[{"active":"1","text":"Imported","block_script":["analytics"]}]');
        \add_filter('wp_redirect', static function (string $location): void {
            throw new \RuntimeException($location);
        });

        try {
            (new Settings())->settings_import();
            $this->fail('The import did not redirect');
        } catch (\RuntimeException $redirect) {
            $this->assertSame(\admin_url('options-general.php?page=italy-cookie-choices'), $redirect->getMessage());
        }

        $this->assertSame(
            ['active' => '1', 'text' => 'Imported', 'block_script' => ['analytics']],
            \get_option(self::OPTION_NAME)
        );
    }

    private function captureDie(): void
    {
        \add_filter('wp_die_handler', static function (): callable {
            return static function ($message, $title, $args): void {
                throw new \RuntimeException((string) \wp_json_encode([$message, $title, $args]));
            };
        }, PHP_INT_MAX);
    }

    private function die(): array
    {
        try {
            (new Settings())->settings_import();
        } catch (\RuntimeException $die) {
            return \json_decode($die->getMessage(), true);
        }

        $this->fail('The import did not die');
    }

    private function prepareImport(string $name, string $content): void
    {
        \file_put_contents($this->importFile, $content);
        $_POST = [
            'icc_action' => 'import_settings',
            'icc_import_nonce' => \wp_create_nonce('icc_import_nonce'),
        ];
        $_FILES = [
            'icc_import_file' => [
                'name' => $name,
                'size' => \strlen($content),
                'tmp_name' => $this->importFile,
            ],
        ];
    }

    private function export(): ?string
    {
        \ob_start();
        $result = (new Settings())->settings_export();
        $output = (string) \ob_get_clean();
        $this->assertSame('', $output);

        return $result;
    }
}
