<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;
use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * The JavaScript consent API in assets/src/consent, built with npm run build.
 */
final class ConsentCest
{
    private const BANNER = '#icc-banner';

    private const ACCEPT = '#icc-banner [data-icc-action="accept-all"]';

    private const CONSENT_COOKIE = 'icc_consent';

    private const ALL_CATEGORIES = ['necessary', 'preferences', 'statistics', 'marketing'];

    private const DAY = 24 * 60 * 60;

    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- Codeception hook
    public function _before(E2eTester $I): void
    {
        $I->haveOptionInDatabase('icc_e2e_events', '1');
        $I->haveCookieChoicesOptions([]);
    }

    public function theApiIsReadyWithoutAConsent(E2eTester $I): void
    {
        $I->amOnPage('/');

        $I->waitForJS('return window.iccEvents.length > 0;');
        // WebDriver sorts the keys of the objects it returns
        $I->assertEquals(
            [['name' => 'italy-cookie-choices:ready', 'detail' => ['consent' => null]]],
            $I->executeJS('return window.iccEvents;')
        );
        $I->assertSame(self::ALL_CATEGORIES, $I->executeJS(
            'return window.italyCookieChoices.categories().map((category) => category.id);'
        ));
        $I->assertTrue($I->executeJS('return window.italyCookieChoices.hasConsent("necessary");'));
        $I->assertFalse($I->executeJS('return window.italyCookieChoices.hasConsent("statistics");'));
    }

    public function theBannerGivesTheConsentToAllCategories(E2eTester $I): void
    {
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::ACCEPT);

        $I->waitForJS('return window.italyCookieChoices.getConsent() !== null;');
        $I->assertSame(self::ALL_CATEGORIES, $this->consentCategories($I));
        $I->assertSame(E2eTester::COOKIE_VALUE, $I->grabCookie(E2eTester::COOKIE_NAME));
        $I->assertEquals(
            [
                'name' => 'italy-cookie-choices:consent-changed',
                'categories' => self::ALL_CATEGORIES,
                'previous' => null,
            ],
            $I->executeJS(
                'const event = window.iccEvents[window.iccEvents.length - 1];'
                . 'return {'
                . 'name: event.name, categories: event.detail.consent.categories, previous: event.detail.previous'
                . '};'
            )
        );
    }

    public function theConsentByCategoryIsKeptOnTheNextPage(E2eTester $I): void
    {
        $I->amOnPage('/');
        $I->executeJS('window.italyCookieChoices.setConsent(["statistics"]);');

        $I->amOnPage('/');

        $I->assertSame(['necessary', 'statistics'], $I->executeJS(
            'return window.italyCookieChoices.getConsent().categories;'
        ));
        $I->assertTrue($I->executeJS('return window.italyCookieChoices.hasConsent("statistics");'));
        $I->assertFalse($I->executeJS('return window.italyCookieChoices.hasConsent("marketing");'));
    }

    public function theConsentLastsSixMonths(E2eTester $I): void
    {
        $I->amOnPage('/');

        $I->executeJS('window.italyCookieChoices.acceptAll();');

        $expiry = $I->executeInSelenium(
            static fn(RemoteWebDriver $driver): ?int => $driver
                ->manage()
                ->getCookieNamed(self::CONSENT_COOKIE)
                ?->getExpiry()
        );
        $I->assertIsInt($expiry);
        $I->assertEqualsWithDelta(\time() + 182 * self::DAY, $expiry, 120);
    }

    public function aNewPolicyVersionAsksTheConsentAgain(E2eTester $I): void
    {
        $I->amOnPage('/');
        $I->executeJS('window.italyCookieChoices.acceptAll();');

        $I->haveOptionInDatabase('icc_e2e_policy_version', '2');
        $I->amOnPage('/');

        $I->assertNull($I->executeJS('return window.italyCookieChoices.getConsent();'));
        $I->assertFalse($I->executeJS('return window.italyCookieChoices.hasConsent("statistics");'));
    }

    /**
     * The 2.x consent may come from a scroll or a second page view, the visitor is asked again.
     */
    public function theConsentGivenWithThe2xBannerIsAskedAgain(E2eTester $I): void
    {
        $I->amOnPage('/');
        $I->setCookie(E2eTester::COOKIE_NAME, E2eTester::COOKIE_VALUE);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->assertNull($I->executeJS('return window.italyCookieChoices.getConsent();'));
        $I->dontSeeCookie(E2eTester::COOKIE_NAME);
    }

    public function theConsentCanBeWithdrawn(E2eTester $I): void
    {
        $I->amOnPage('/');
        $I->executeJS('window.italyCookieChoices.rejectAll();');

        $I->executeJS('window.italyCookieChoices.withdraw();');

        $I->dontSeeCookie(self::CONSENT_COOKIE);
        $I->assertEquals(
            ['consent' => null, 'previous' => ['necessary']],
            $I->executeJS(
                'const event = window.iccEvents[window.iccEvents.length - 1];'
                . 'return {consent: event.detail.consent, previous: event.detail.previous.categories};'
            )
        );
    }

    public function theApiIsNotLoadedWhenThePluginIsNotActive(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['active' => null]);

        $I->amOnPage('/');

        $I->assertNull($I->executeJS('return window.italyCookieChoices ?? null;'));
    }

    /**
     * @return mixed The categories of the consent in the browser.
     */
    private function consentCategories(E2eTester $I): mixed
    {
        return $I->executeJS('return window.italyCookieChoices.getConsent().categories;');
    }
}
