<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;

final class AdminCest
{
    private const SETTINGS_PAGE = '/options-general.php?page=italy-cookie-choices';

    public function itShowsTheSettingsPage(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->loginAsAdmin();

        $I->amOnAdminPage(self::SETTINGS_PAGE);

        $I->see('Italy Cookie Choices options page', 'h2');
        $I->see('Style settings', 'h2');
        $I->see('Advanced settings', 'h2');
        $I->seeElement('#italy-cookie-choices-ID');
        $I->see('Export Settings');
        $I->see('Import Settings');
    }

    public function itIsListedInTheSettingsMenu(E2eTester $I): void
    {
        $I->loginAsAdmin();

        $I->amOnAdminPage('/');

        $I->seeElementInDOM('#menu-settings a[href="options-general.php?page=italy-cookie-choices"]');
    }

    public function itCreatesTheDefaultOptionsOnTheFirstAdminVisit(E2eTester $I): void
    {
        $I->dontHaveOptionInDatabase(E2eTester::OPTION_NAME);
        $I->loginAsAdmin();

        $I->amOnAdminPage('/');

        $I->assertSame(
            [
                'text' => '',
                'url' => '',
                'anchor_text' => '',
                'button_text' => '',
                'cookie_name' => 'displayCookieConsent',
                'cookie_value' => 'y',
                'content_message_text' => '',
                'content_message_button_text' => '',
            ],
            $I->grabOptionFromDatabase(E2eTester::OPTION_NAME)
        );
    }

    public function itSavesTheSettings(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['active' => null]);
        $I->loginAsAdmin();
        $I->amOnAdminPage(self::SETTINGS_PAGE);

        $I->checkOption(['name' => 'italy_cookie_choices[active]']);
        $I->selectOption(['name' => 'italy_cookie_choices[banner]'], '2');
        $I->fillField(['name' => 'italy_cookie_choices[anchor_text]'], 'Policy <b>page</b>');
        $I->fillField(['name' => 'italy_cookie_choices[button_text]'], 'Got it');
        $I->selectOption(['name' => 'italy_cookie_choices[js_template]'], 'bigbutton');
        $I->fillField(['name' => 'italy_cookie_choices[cookie_name]'], 'savedConsent');
        $I->click('Save Changes');

        $I->see('Settings saved.');
        $options = $I->grabOptionFromDatabase(E2eTester::OPTION_NAME);
        $I->assertSame('1', $options['active']);
        $I->assertSame('2', $options['banner']);
        $I->assertSame('Policy page', $options['anchor_text']);
        $I->assertSame('Got it', $options['button_text']);
        $I->assertSame('bigbutton', $options['js_template']);
        $I->assertSame('savedConsent', $options['cookie_name']);
        $I->seeCheckboxIsChecked(['name' => 'italy_cookie_choices[active]']);
        $I->seeInField(['name' => 'italy_cookie_choices[button_text]'], 'Got it');
    }

    public function theSavedSettingsChangeTheBanner(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->loginAsAdmin();
        $I->amOnAdminPage(self::SETTINGS_PAGE);
        $I->fillField(['name' => 'italy_cookie_choices[button_text]'], 'Got it');
        $I->click('Save Changes');
        $I->see('Settings saved.');

        $I->logOut();
        $I->amOnPage('/');

        $I->waitForElementVisible('#cookieChoiceInfo');
        $I->see('Got it', '#cookieChoiceDismiss');
    }

    public function itRestoresTheDefaultCookieName(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->loginAsAdmin();
        $I->amOnAdminPage(self::SETTINGS_PAGE);

        $I->fillField(['name' => 'italy_cookie_choices[cookie_name]'], '');
        $I->fillField(['name' => 'italy_cookie_choices[cookie_value]'], '');
        $I->click('Save Changes');

        $I->see('Cookie name field it can\'t be empty. Restored default name.');
        $I->see('Cookie value field it can\'t be empty. Restored default value.');
        $options = $I->grabOptionFromDatabase(E2eTester::OPTION_NAME);
        $I->assertSame('displayCookieConsent', $options['cookie_name']);
        $I->assertSame('y', $options['cookie_value']);
    }

    public function itExportsTheSettings(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['button_text' => 'Exported']);
        $I->loginAsAdmin();
        $I->amOnAdminPage(self::SETTINGS_PAGE);

        $json = $I->executeAsyncJS(
            'const done = arguments[0];'
            . 'const form = document.querySelector("input[value=export_settings]").form;'
            . 'fetch(location.href, {method: "POST", body: new FormData(form), credentials: "same-origin"})'
            . '.then(function (response) { return response.text(); }).then(done);'
        );

        $I->assertSame([$I->grabOptionFromDatabase(E2eTester::OPTION_NAME)], \json_decode($json, true));
    }

    public function itImportsTheSettings(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->loginAsAdmin();
        $I->amOnAdminPage(self::SETTINGS_PAGE);

        $I->attachFile('input[name=icc_import_file]', 'settings-import.json');
        $I->click(['css' => 'form[enctype="multipart/form-data"] input[type=submit]']);

        $I->seeInCurrentUrl('options-general.php?page=italy-cookie-choices');
        // The import goes through the registered sanitize callback, that adds the style defaults
        $I->assertSame(
            [
                'active' => '1',
                'banner' => '3',
                'text' => 'Imported text',
                'url' => 'http://wordpress/imported-policy/',
                'anchor_text' => 'Imported anchor',
                'button_text' => 'Imported button',
                'banner_bg' => '#fff',
                'banner_text_color' => '#000',
                'bannerStyle' => 'bannerStyle',
                'contentStyle' => 'contentStyle',
                'consentText' => 'consentText',
                'infoClass' => 'italybtn',
                'closeClass' => 'italybtn',
                'cookie_name' => 'importedConsent',
                'cookie_value' => 'yes',
            ],
            $I->grabOptionFromDatabase(E2eTester::OPTION_NAME)
        );
    }

    public function itRejectsImportsThatAreNotJson(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->loginAsAdmin();
        $I->amOnAdminPage(self::SETTINGS_PAGE);

        $I->attachFile('input[name=icc_import_file]', 'settings-import.txt');
        $I->click(['css' => 'form[enctype="multipart/form-data"] input[type=submit]']);

        $I->see('Please upload a valid .json file');
    }

    public function editorsCannotOpenTheSettingsPage(E2eTester $I): void
    {
        $I->haveUserInDatabase('editor', 'editor', ['user_pass' => 'password']);
        $I->loginAs('editor', 'password');

        $I->amOnAdminPage(self::SETTINGS_PAGE);

        $I->see('Sorry, you are not allowed to access this page.');
    }

    /**
     * Current behaviour: the links are hooked to the basename of define-constants.php,
     * so they never show up in the plugins list.
     */
    public function thePluginActionLinksAreNotShown(E2eTester $I): void
    {
        $I->loginAsAdmin();

        $I->amOnPluginsPage();

        $I->seePluginActivated('italy-cookie-choices');
        $I->dontSeeLink('Documentation');
        $I->dontSeeElement(
            'tr[data-slug="italy-cookie-choices"] a[href="options-general.php?page=italy-cookie-choices"]'
        );
    }
}
