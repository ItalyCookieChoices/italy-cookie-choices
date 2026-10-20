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
        $I->waitForElementVisible('#cookieChoiceInfo');

        $I->click('I agree', '.entry-content');

        $I->dontSeeElementInDOM('#cookieChoiceInfo');
        $I->assertSame(E2eTester::COOKIE_VALUE, $I->grabCookie(E2eTester::COOKIE_NAME));
    }

    public function theDeleteCookieButtonIsPrinted(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePolicyPage($I, '[delete_cookie]');

        $I->amOnPage('/cookie-policy/');

        $I->see('Delete cookie', '.entry-content .ele button');
    }

    /**
     * Current behaviour: deleteCookie() reads an undefined global, the cookie stays.
     */
    public function theDeleteCookieButtonDoesNotDeleteTheCookie(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePolicyPage($I, '[delete_cookie]');
        $I->amOnPage('/');
        $I->setCookie(E2eTester::COOKIE_NAME, E2eTester::COOKIE_VALUE);
        $I->amOnPage('/cookie-policy/');

        $I->click('Delete cookie');

        $I->assertSame(E2eTester::COOKIE_VALUE, $I->grabCookie(E2eTester::COOKIE_NAME));
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
