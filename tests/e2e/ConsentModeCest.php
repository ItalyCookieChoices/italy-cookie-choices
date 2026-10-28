<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;

final class ConsentModeCest
{
    private const BANNER = '#icc-banner';
    private const SCRIPT = '#italy-cookie-choices-consent-mode-js';
    private const DENIED = [
        'security_storage' => 'granted',
        'functionality_storage' => 'denied',
        'personalization_storage' => 'denied',
        'analytics_storage' => 'denied',
        'ad_storage' => 'denied',
        'ad_user_data' => 'denied',
        'ad_personalization' => 'denied',
    ];

    public function everyConsentIsDeniedBeforeTheChoice(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['consent_mode' => '1']);

        $I->amOnPage('/');

        $I->seeElementInDOM('head ' . self::SCRIPT);
        // The browser sends the objects with the keys sorted.
        $I->assertEquals(
            [
                ['consent', 'default', [...self::DENIED, 'wait_for_update' => 500]],
                ['set', 'ads_data_redaction', true],
            ],
            $this->consentCalls($I)
        );
    }

    public function acceptingEverythingGrantsEveryConsent(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['consent_mode' => '1']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::BANNER . ' [data-icc-action="accept-all"]');

        $I->assertEquals(
            ['consent', 'update', \array_map(static fn(): string => 'granted', self::DENIED)],
            $this->lastConsentCall($I)
        );
    }

    public function rejectingEverythingKeepsTheConsentDenied(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['consent_mode' => '1']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::BANNER . ' [data-icc-action="reject-all"]');

        $I->assertEquals(['consent', 'update', self::DENIED], $this->lastConsentCall($I));
    }

    public function theStoredConsentIsSentOnTheNextPage(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['consent_mode' => '1']);
        $I->amOnPage('/');
        $I->executeJS('window.italyCookieChoices.setConsent( [ "statistics" ] );');

        $I->amOnPage('/');

        $calls = $this->consentCalls($I);
        $I->assertSame('default', $calls[0][1] ?? null);
        $I->assertEquals(
            ['consent', 'update', [...self::DENIED, 'analytics_storage' => 'granted']],
            $calls[2] ?? null
        );
    }

    public function theGoogleTagsWaitForTheConsentInTheBasicMode(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['consent_mode' => '1', 'ga_measurement_id' => 'G-ICCTEST']);

        $I->amOnPage('/');

        $I->seeElementInDOM('script#icc-google-analytics-js[type="text/plain"][data-category="statistics"]');
        $I->assertTrue($I->executeJS(
            'const consentMode = document.querySelector("' . self::SCRIPT . '");'
            . 'const analytics = document.querySelector("#icc-google-analytics-js");'
            . 'return Boolean(consentMode.compareDocumentPosition(analytics) & Node.DOCUMENT_POSITION_FOLLOWING);'
        ));
    }

    public function theGoogleTagsLoadBeforeTheConsentInTheAdvancedMode(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([
            'consent_mode' => '1',
            'consent_mode_advanced' => '1',
            'ga_measurement_id' => 'G-ICCTEST',
        ]);

        $I->amOnPage('/');

        $I->seeElementInDOM('script#icc-google-analytics-js');
        $I->dontSeeElementInDOM('script#icc-google-analytics-js[type="text/plain"]');
        $I->assertSame(['consent', 'default'], \array_slice($this->consentCalls($I)[0] ?? [], 0, 2));
    }

    public function nothingIsSentWhenTheConsentModeIsOff(E2eTester $I): void
    {
        $I->haveOptionInDatabase('italy_cookie_choices_version', 6);
        $I->haveCookieChoicesOptions(['consent_mode' => null]);

        $I->amOnPage('/');

        $I->dontSeeElementInDOM(self::SCRIPT);
        $I->assertSame([], $this->consentCalls($I));
    }

    /**
     * The calls of gtag() in the data layer, each one is an arguments object.
     *
     * @return list<list<mixed>>
     */
    private function consentCalls(E2eTester $I): array
    {
        $calls = $I->executeJS(
            'return (window.dataLayer || [])'
            . '.filter((entry) => typeof entry === "object" && entry !== null && "length" in entry)'
            . '.map((entry) => Array.from(entry));'
        );
        $I->assertIsArray($calls);

        /** @var list<list<mixed>> $calls */
        return $calls;
    }

    /**
     * @return list<mixed>|null
     */
    private function lastConsentCall(E2eTester $I): ?array
    {
        $consent = \array_values(\array_filter(
            $this->consentCalls($I),
            static fn(array $call): bool => ($call[0] ?? null) === 'consent'
        ));

        return $consent === [] ? null : $consent[\count($consent) - 1];
    }
}
