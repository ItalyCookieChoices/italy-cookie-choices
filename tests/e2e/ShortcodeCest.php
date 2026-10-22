<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;

final class ShortcodeCest
{
    public function theAcceptButtonAcceptsTheCookies(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['button_text' => 'I agree']);
        $this->havePolicyPage($I, '[accept_button]');
        $I->amOnPage('/cookie-policy/');
        $I->waitForElementVisible('#icc-banner');

        $I->click('I agree', '.entry-content');

        $I->waitForElementNotVisible('#icc-banner');
        $I->assertSame(E2eTester::COOKIE_VALUE, $I->grabCookie(E2eTester::COOKIE_NAME));
    }

    public function thePreferencesButtonWithdrawsTheConsent(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePolicyPage(
            $I,
            '[cookie_preferences text="Change your choice"]<iframe src="https://www.youtube.com/embed/a"></iframe>'
        );
        $I->amOnPage('/');
        $I->executeJS('window.italyCookieChoices.acceptAll();');
        $I->amOnPage('/cookie-policy/');
        $I->seeElement('.entry-content iframe[src*="youtube.com"]');

        $I->click('Change your choice');
        $I->waitForElementVisible('#icc-preferences');
        $I->click('#icc-preferences [data-icc-action="reject-all"]');

        $I->waitForElementNotVisible('#icc-preferences');
        $I->assertSame(['necessary'], $I->executeJS('return window.italyCookieChoices.getConsent().categories;'));
        $I->waitForElement('.entry-content iframe[data-icc-src*="youtube.com"]:not([src])');
        $I->seeElement('.entry-content .icc-placeholder');
    }

    /**
     * Changed in #237: the 2.x button called an undefined function, it opens the preferences now (#209).
     */
    public function theDeleteCookieButtonOpensThePreferences(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePolicyPage($I, '[delete_cookie]');
        $I->amOnPage('/cookie-policy/');

        $I->click('Cookie preferences', '.entry-content');

        $I->waitForElementVisible('#icc-preferences');
    }

    public function theShortcodesAreNotRegisteredWhenNotActive(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['active' => null]);
        $this->havePolicyPage($I, '[accept_button] [delete_cookie]');

        $I->amOnPage('/cookie-policy/');

        $I->see('[accept_button] [delete_cookie]', '.entry-content');
    }

    private function havePolicyPage(E2eTester $I, string $content): void
    {
        $I->havePageInDatabase([
            'post_name' => 'cookie-policy',
            'post_title' => 'Cookie policy',
            'post_content' => $content,
        ]);
    }
}
