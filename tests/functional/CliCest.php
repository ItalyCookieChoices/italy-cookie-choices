<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Functional;

use FunctionalTester;

/**
 * The commands of #285, run by WP-CLI as the site owner runs them.
 */
final class CliCest
{
    private const OPTION = 'italy_cookie_choices';
    private const LOG_TABLE = 'wp_icc_consent_log';

    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- Codeception method
    public function _before(FunctionalTester $I): void
    {
        // At the current version, so the migration does not change the option of the test.
        $I->haveOptionInDatabase('italy_cookie_choices_version', 10);
        $I->haveOptionInDatabase(self::OPTION, [
            'active' => '1',
            'text' => 'Saved text',
            'consent_mode' => '1',
            'consent_log' => '1',
            'consent_log_retention' => '12',
        ]);
    }

    public function theRootCommandListsTheCommands(FunctionalTester $I): void
    {
        $I->cli(['cookie-choices']);

        $I->seeInShellOutput('wp cookie-choices log <command>');
        $I->seeInShellOutput('wp cookie-choices settings <command>');
        $I->seeInShellOutput('wp cookie-choices status');
    }

    public function theStatusIsPrintedAsJson(FunctionalTester $I): void
    {
        $I->assertSame(0, $I->cli(['cookie-choices', 'status', '--format=json']));

        $status = \json_decode($I->grabLastShellOutput(), true);
        $I->assertIsArray($status);
        $values = \array_column($status, 'value', 'setting');
        $I->assertSame('on', $values['banner'] ?? null);
        $I->assertSame('basic', $values['consent_mode'] ?? null);
    }

    public function theSettingsAreExportedAndImported(FunctionalTester $I): void
    {
        $file = '/tmp/icc-functional-settings.json';
        $I->assertSame(0, $I->cli(['cookie-choices', 'settings', 'export', '--file=' . $file]));
        $I->seeInShellOutput('Success: The settings are exported to ' . $file . '.');

        $I->haveOptionInDatabase(self::OPTION, ['active' => '1', 'text' => 'Changed']);
        $I->assertSame(0, $I->cli(['cookie-choices', 'settings', 'import', $file, '--yes']));

        $I->seeInShellOutput('Success: The settings are imported.');
        $saved = $I->grabOptionFromDatabase(self::OPTION);
        $I->assertIsArray($saved);
        $I->assertSame('Saved text', $saved['text'] ?? null);
    }

    public function anInvalidSettingsFileExitsWithItsCode(FunctionalTester $I): void
    {
        $I->assertSame(4, $I->cli(['cookie-choices', 'settings', 'import', '/tmp/icc-missing.json', '--yes']));

        $I->assertStringContainsString('The file cannot be read.', $I->grabLastShellErrorOutput());
    }

    public function theLogIsExportedAndPurged(FunctionalTester $I): void
    {
        // The plugin creates its table on the first request, a command is one.
        $I->dontHaveOptionInDatabase('italy_cookie_choices_log_table_version');
        $I->cli(['cookie-choices', 'status']);
        $I->haveInDatabase(self::LOG_TABLE, $this->record('3f2504e0-4f89-41d3-9a0c-0305e82c3301', '-2 years'));
        $I->haveInDatabase(self::LOG_TABLE, $this->record('3f2504e0-4f89-41d3-9a0c-0305e82c3302', '-1 day'));

        $I->assertSame(0, $I->cli(['cookie-choices', 'log', 'export']));
        $I->seeInShellOutput('consent_id,recorded_at,policy_version,categories,withdrawn');
        $I->seeInShellOutput('3f2504e0-4f89-41d3-9a0c-0305e82c3301');

        $I->assertSame(0, $I->cli(['cookie-choices', 'log', 'purge', '--yes']));
        $I->seeInShellOutput('Success: 1 record deleted.');
        $I->dontSeeInDatabase(self::LOG_TABLE, ['consent_id' => '3f2504e0-4f89-41d3-9a0c-0305e82c3301']);
        $I->seeInDatabase(self::LOG_TABLE, ['consent_id' => '3f2504e0-4f89-41d3-9a0c-0305e82c3302']);
    }

    public function anInvalidDateExitsWithItsCode(FunctionalTester $I): void
    {
        $I->assertSame(6, $I->cli(['cookie-choices', 'log', 'purge', '--before=yesterday', '--yes']));

        $I->assertStringContainsString(
            'The date is not valid, use the format YYYY-MM-DD.',
            $I->grabLastShellErrorOutput()
        );
    }

    /**
     * @return array{consent_id: string, categories: string, policy_version: string, recorded_at: string}
     */
    private function record(string $id, string $date): array
    {
        return [
            'consent_id' => $id,
            'categories' => 'necessary',
            'policy_version' => '1',
            'recorded_at' => (new \DateTimeImmutable($date, new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
        ];
    }
}
