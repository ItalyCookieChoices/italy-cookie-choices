<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;

/**
 * The cookies of the categories without the consent are deleted.
 */
final class CookieCleanupCest
{
    private const BANNER = '#icc-banner';

    public function rejectingDeletesTheCookiesOfTheOptionalCategories(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);
        $this->setCookies($I, '_ga', '_ga_ICC', '_fbp', 'icc_theme');

        $I->click(self::BANNER . ' [data-icc-action="reject-all"]');

        $I->assertSame(['icc_consent', 'icc_theme'], $this->cookieNames($I));
    }

    public function theCookiesOfTheAcceptedCategoriesAreKept(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $this->setCookies($I, '_ga', '_fbp');

        $I->executeJS('window.italyCookieChoices.setConsent( [ "statistics" ] );');

        $I->assertSame(['_ga', 'icc_consent'], $this->cookieNames($I));
    }

    public function theCookiesWithoutAConsentAreDeletedOnLoad(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $this->setCookies($I, '_ga', '_fbp');

        $I->amOnPage('/');

        $I->assertSame([], $this->cookieNames($I));
    }

    private function setCookies(E2eTester $I, string ...$names): void
    {
        foreach ($names as $name) {
            $I->executeJS('document.cookie = "' . $name . '=1; path=/";');
        }
    }

    /**
     * @return list<string>
     */
    private function cookieNames(E2eTester $I): array
    {
        $names = $I->executeJS(
            'return document.cookie.split("; ").filter((c) => c !== "").map((c) => c.split("=")[0]).sort();'
        );
        $I->assertIsArray($names);

        /** @var list<string> $names */
        return $names;
    }
}
