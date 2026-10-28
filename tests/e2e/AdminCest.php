<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;
use Facebook\WebDriver\WebDriverKeys;
use ItalyCookieChoices\Settings\Schema;

final class AdminCest
{
    private const SETTINGS_PAGE = '/options-general.php?page=italy-cookie-choices';
    private const NOTICE = '.components-notice__content';
    private const TABS = '.components-tab-panel__tabs';

    public function itShowsTheSettingsPage(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);

        $this->openTheSettings($I);

        $I->see('Italy Cookie Choices', 'h1');
        foreach (['Banner', 'Style', 'Categories', 'Blocking', 'Integrations', 'Consent log', 'Tools'] as $tab) {
            $I->see($tab, self::TABS);
        }
        $I->seeInField('Banner text', 'This site uses cookies');
        $I->seeElement('.icc-admin__actions button.is-primary[disabled]');
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
                'consent_log' => '1',
                'consent_log_retention' => '12',
                'consent_mode' => '1',
                'youtube_nocookie' => '1',
            ],
            $I->grabOptionFromDatabase(E2eTester::OPTION_NAME)
        );
    }

    public function itSavesTheSettings(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['active' => null]);
        $this->openTheSettings($I);

        $I->checkOption('Show the banner');
        $I->fillField('Policy link text', 'Policy <b>page</b>');
        $I->fillField('Accept button text', 'Got it');
        $this->openTab($I, 'Style');
        $I->selectOption('Position', 'Modal window');
        $I->selectOption('Template', 'Big buttons');
        $this->save($I);

        $I->see('Settings saved.', self::NOTICE);
        $options = $this->savedOptions($I);
        $I->assertSame('1', $options['active']);
        $I->assertSame('2', $options['banner']);
        $I->assertSame('Policy page', $options['anchor_text']);
        $I->assertSame('Got it', $options['button_text']);
        $I->assertSame('bigbutton', $options['js_template']);

        $I->reloadPage();
        $I->waitForElement('.icc-admin__tabs');
        $I->seeCheckboxIsChecked('Show the banner');
        $I->seeInField('Accept button text', 'Got it');
    }

    public function theSavedSettingsChangeTheBanner(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->openTheSettings($I);
        $I->fillField('Accept button text', 'Got it');
        $this->save($I);
        $I->see('Settings saved.', self::NOTICE);

        $I->logOut();
        $I->amOnPage('/');

        $I->waitForElementVisible('#icc-banner');
        $I->see('Got it', '#icc-banner [data-icc-action="accept-all"]');
    }

    public function thePreviewShowsTheSettingsNotSavedYet(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->openTheSettings($I);

        $I->fillField('Banner text', 'Text not saved yet');

        $I->waitForJS(
            'const frame = document.querySelector(".icc-admin__preview-frame");'
            . 'return frame !== null && frame.srcdoc.includes("Text not saved yet");',
            10
        );
        $I->click('Preferences', '.icc-admin__preview');
        $I->waitForJS(
            'return document.querySelector(".icc-admin__preview-frame").srcdoc'
            . '.includes(\'id="icc-preferences"\');',
            10
        );
        $I->assertSame('This site uses cookies', $this->savedOptions($I)['text']);
    }

    public function itFindsThePolicyPage(E2eTester $I): void
    {
        $I->havePageInDatabase(['post_title' => 'Cookie policy', 'post_name' => 'cookie-policy']);
        $I->haveCookieChoicesOptions(['url' => null, 'slug' => null]);
        $this->openTheSettings($I);

        $I->fillField('Policy page', 'Cookie');
        $I->waitForText('Cookie policy', 10, '.components-form-token-field__suggestion');
        $I->click('.components-form-token-field__suggestion');
        $this->save($I);

        $options = $this->savedOptions($I);
        $I->assertSame('cookie-policy', $options['slug']);
        $I->assertIsString($options['url']);
        $I->assertStringEndsWith('/cookie-policy/', $options['url']);
    }

    public function itRenamesACategory(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->openTheSettings($I);
        $this->openTab($I, 'Categories');

        $I->fillField('.icc-admin__panel fieldset:nth-of-type(3) input', 'Analytics');
        $this->save($I);

        $I->assertSame(['statistics' => ['label' => 'Analytics']], $this->savedOptions($I)['categories']);
        $I->logOut();
        $I->amOnPage('/');
        $I->assertSame(
            'Analytics',
            $I->executeJS('return document.querySelector(\'label[for="icc-category-statistics"]\').textContent.trim();')
        );
    }

    public function itChangesTheVendors(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->openTheSettings($I);
        $this->openTab($I, 'Blocking');

        $I->uncheckOption('YouTube');
        $I->click('Add a vendor');
        $I->fillField('Name', 'Ads');
        $I->fillField('Addresses and scripts to block', 'ads.example.com');
        $I->pressKey('.icc-admin__group:nth-of-type(2) .components-form-token-field__input', WebDriverKeys::ENTER);
        $this->save($I);

        $options = $this->savedOptions($I);
        $I->assertSame(['youtube'], $options['disabled_vendors']);
        $I->assertSame(
            [['id' => 'custom-1', 'name' => 'Ads', 'category' => 'marketing', 'patterns' => ['ads.example.com']]],
            $options['custom_vendors']
        );
    }

    public function itSavesTheIntegrations(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->openTheSettings($I);
        $this->openTab($I, 'Integrations');

        $I->waitForText('Load the Google tags before the consent (advanced mode)', 5);
        $I->seeCheckboxIsChecked('Send the Consent Mode signals');
        $I->checkOption('Load the Google tags before the consent (advanced mode)');
        $I->fillField('Google Analytics measurement ID', 'G-ICCTEST');
        $I->fillField('Facebook pixel ID', '123456');
        $this->save($I);

        $I->see('Settings saved.', self::NOTICE);
        $options = $this->savedOptions($I);
        $I->assertSame('1', $options['consent_mode_advanced']);
        $I->assertSame('G-ICCTEST', $options['ga_measurement_id']);
        $I->assertSame('123456', $options['facebook_pixel_id']);
    }

    public function anEmptyCookieNameIsNotSaved(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->openTheSettings($I);
        $this->openTab($I, 'Tools');

        // Cleared with the keyboard, React does not see a value set by the driver.
        $I->pressKey(
            '.icc-admin__panel fieldset:first-of-type input',
            [WebDriverKeys::CONTROL, 'a'],
            WebDriverKeys::BACKSPACE
        );
        $this->save($I);

        $I->waitForElementVisible('.components-notice.is-error', 10);
        $I->assertSame('displayCookieConsent', $this->savedOptions($I)['cookie_name']);
    }

    public function itExportsTheSettings(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['button_text' => 'Exported']);
        $this->openTheSettings($I);
        $this->openTab($I, 'Tools');
        // The file is read from the link instead of downloaded.
        $I->executeJS(
            'window.iccExported = null;'
            . 'URL.createObjectURL = (blob) => { blob.text().then((text) => { window.iccExported = text; });'
            . ' return "about:blank"; };'
            . 'URL.revokeObjectURL = () => {};'
            . 'HTMLAnchorElement.prototype.click = () => {};'
        );

        $I->click('Export the settings');

        $I->waitForJS('return window.iccExported !== null;', 10);
        $json = $I->executeJS('return window.iccExported;');
        $I->assertIsString($json);
        $file = \json_decode($json, true);
        $I->assertIsArray($file);
        $I->assertSame('italy-cookie-choices', $file['plugin']);
        $I->assertSame(Schema::VERSION, $file['version']);
        $I->assertIsArray($file['settings']);
        $I->assertSame('Exported', $file['settings']['button_text']);
    }

    public function itImportsTheSettingsOfThe2xVersions(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->openTheSettings($I);
        $this->openTab($I, 'Tools');

        $I->attachFile('#icc-admin-import', 'settings-import.json');

        $I->waitForText('Settings imported.', 10, self::NOTICE);
        $options = $this->savedOptions($I);
        $I->assertSame('3', $options['banner']);
        $I->assertSame('Imported text', $options['text']);
        $I->assertSame('importedConsent', $options['cookie_name']);
        $I->assertSame('1', $options['consent_log']);
        $this->openTab($I, 'Banner');
        $I->seeInField('Banner text', 'Imported text');
    }

    public function itRejectsImportsThatAreNotJson(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->openTheSettings($I);
        $this->openTab($I, 'Tools');

        $I->attachFile('#icc-admin-import', 'settings-import.txt');

        $I->waitForText('The file is not a valid JSON file.', 10, self::NOTICE);
        $I->assertSame('This site uses cookies', $this->savedOptions($I)['text']);
    }

    public function editorsCannotOpenTheSettingsPage(E2eTester $I): void
    {
        $I->haveUserInDatabase('editor', 'editor', ['user_pass' => 'password']);
        $I->loginAs('editor', 'password');

        $I->amOnAdminPage(self::SETTINGS_PAGE);

        $I->see('Sorry, you are not allowed to access this page.');
    }

    public function thePluginActionLinksAreShown(E2eTester $I): void
    {
        $I->loginAsAdmin();

        $I->amOnPluginsPage();

        $I->seePluginActivated('italy-cookie-choices');
        $I->see('Settings', 'tr[data-slug="italy-cookie-choices"] .settings a[href$="page=italy-cookie-choices"]');
        $I->see(
            'Documentation',
            'tr[data-slug="italy-cookie-choices"] .documentation a[href$="/italy-cookie-choices/wiki"]'
        );
    }

    public function theSettingsPageHasNoAccessibilityViolations(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->openTheSettings($I);

        $I->assertSame([], $I->grabAccessibilityViolations('.icc-admin'));
    }

    private function openTheSettings(E2eTester $I): void
    {
        $I->loginAsAdmin();
        $I->amOnAdminPage(self::SETTINGS_PAGE);
        $I->waitForElement('.icc-admin__tabs', 10);
    }

    private function openTab(E2eTester $I, string $tab): void
    {
        $I->click($tab, self::TABS);
    }

    private function save(E2eTester $I): void
    {
        $I->click('Save changes');
        $I->waitForElementVisible('.components-notice', 10);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function savedOptions(E2eTester $I): array
    {
        $options = $I->grabOptionFromDatabase(E2eTester::OPTION_NAME);
        $I->assertIsArray($options);

        return $options;
    }
}
