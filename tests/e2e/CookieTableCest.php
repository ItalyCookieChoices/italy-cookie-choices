<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;

/**
 * The cookie table of the cookie policy, from the shortcode and the block.
 */
final class CookieTableCest
{
    private const TABLE = '.icc-cookie-table';

    public function theShortcodeListsTheConsentCookieAndTheVendorsTurnedOn(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->havePageInDatabase(['post_name' => 'cookie-table', 'post_content' => '[cookie_table]']);

        $I->amOnPage('/cookie-table/');

        $I->see('Necessary', self::TABLE . ' caption');
        $I->see('icc_consent', self::TABLE);
        $I->see('_ga', self::TABLE);
        $I->see('Google Analytics', self::TABLE);
        // The reCAPTCHA preset starts turned off.
        $I->dontSee('_GRECAPTCHA', self::TABLE);
    }

    public function theSiteCookiesAreListed(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([
            'site_cookies' => [
                ['name' => 'wp_lang', 'category' => 'preferences', 'duration' => 'Session', 'purpose' => 'Language'],
            ],
        ]);
        $I->havePageInDatabase(['post_name' => 'cookie-table', 'post_content' => '[cookie_table]']);

        $I->amOnPage('/cookie-table/');

        $I->see('Preferences', self::TABLE . ' caption');
        $I->see('wp_lang', self::TABLE);
    }

    public function theCookiesOfTheWpConsentApiAreListed(E2eTester $I): void
    {
        $I->haveOptionInDatabase('active_plugins', [
            'italy-cookie-choices/italy-cookie-choices.php',
            'wp-consent-api/wp-consent-api.php',
        ]);
        $I->haveOptionInDatabase('icc_e2e_cookie_info', '1');
        $I->haveCookieChoicesOptions([]);
        $I->havePageInDatabase(['post_name' => 'cookie-table', 'post_content' => '[cookie_table]']);

        $I->amOnPage('/cookie-table/');

        $I->see('icc_e2e_api', self::TABLE);
        $I->see('E2E plugin', self::TABLE);
    }

    public function theBlockPrintsTheTable(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->havePageInDatabase([
            'post_name' => 'cookie-table',
            'post_content' => '<!-- wp:italy-cookie-choices/cookie-table /-->',
        ]);

        $I->amOnPage('/cookie-table/');

        $I->seeElement('.wp-block-italy-cookie-choices-cookie-table ' . self::TABLE);
        $I->see('icc_consent', self::TABLE);
    }
}
