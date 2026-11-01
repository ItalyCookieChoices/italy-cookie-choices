<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;
use Facebook\WebDriver\Remote\RemoteWebDriver;

final class ConsentLogCest
{
    private const BANNER = '#icc-banner';
    private const PREFERENCES = '#icc-preferences';

    public function theChoiceAndItsWithdrawalAreLogged(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['consent_log' => '1']);
        $I->havePageInDatabase([
            'post_name' => 'cookie-policy',
            'post_title' => 'Cookie policy',
            'post_content' => '[cookie_preferences]',
        ]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::BANNER . ' [data-icc-action="accept-all"]');
        $id = $I->executeJS('return window.italyCookieChoices.getConsent().id;');
        $I->assertIsString($id);
        $this->waitForRecords($I, $id, 1);

        $I->amOnPage('/cookie-policy/');
        $I->click('Cookie preferences', '.entry-content');
        $I->waitForElementVisible(self::PREFERENCES);
        $I->click(self::PREFERENCES . ' [data-icc-action="reject-all"]');
        $this->waitForRecords($I, $id, 2);

        $I->assertSame(
            ['necessary preferences statistics marketing', 'necessary'],
            $I->grabColumnFromDatabase($this->table($I), 'categories', ['consent_id' => $id])
        );
    }

    public function nothingIsLoggedWhenTheLogIsOff(E2eTester $I): void
    {
        // A current option, the migration would turn the log on.
        $I->haveOptionInDatabase('italy_cookie_choices_version', 4);
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::BANNER . ' [data-icc-action="accept-all"]');

        $I->dontSeeElementInDOM('script#italy-cookie-choices-consent-log-js');
        $id = $I->executeJS('return window.italyCookieChoices.getConsent().id;');
        $I->assertSame([], $I->grabColumnFromDatabase($this->table($I), 'categories', ['consent_id' => $id]));
    }

    public function theAdministratorExportsTheLog(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['consent_log' => '1']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);
        $I->click(self::BANNER . ' [data-icc-action="reject-all"]');
        $id = $I->executeJS('return window.italyCookieChoices.getConsent().id;');
        $I->assertIsString($id);
        $this->waitForRecords($I, $id, 1);
        $I->loginAsAdmin();
        $I->amOnAdminPage('admin.php?page=italy-cookie-choices');
        $I->waitForElement('.icc-admin__tabs', 10);
        $I->click('Consent log', '.components-tab-panel__tabs');

        $exportUrl = $I->grabAttributeFrom('a[href*="italy_cookie_choices_export_consent_log"]', 'href');
        $I->assertIsString($exportUrl);
        $csv = $I->executeInSelenium(static fn(RemoteWebDriver $driver): mixed => $driver->executeAsyncScript(
            'const done = arguments[arguments.length - 1];'
            . 'fetch(arguments[0]).then((response) => response.text()).then(done);',
            [$exportUrl]
        ));

        $I->assertIsString($csv);
        $I->assertStringStartsWith("consent_id,recorded_at,policy_version,categories,withdrawn\n", $csv);
        $I->assertStringContainsString($id . ',', $csv);
    }

    /**
     * The log is written by a request the page does not wait for.
     */
    private function waitForRecords(E2eTester $I, string $id, int $count): void
    {
        for ($attempt = 0; $attempt < 40; $attempt++) {
            if ($I->grabNumRecords($this->table($I), ['consent_id' => $id]) === $count) {
                return;
            }

            \usleep(250000);
        }

        $I->fail(\sprintf('The log has no %d records for %s', $count, $id));
    }

    private function table(E2eTester $I): string
    {
        return $I->grabPrefixedTableNameFor('icc_consent_log');
    }
}
