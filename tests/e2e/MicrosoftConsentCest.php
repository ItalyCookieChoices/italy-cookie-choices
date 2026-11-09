<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;

/**
 * The consent signals of Microsoft Advertising (UET) and Microsoft Clarity.
 */
final class MicrosoftConsentCest
{
    private const BANNER = '#icc-banner';
    private const SCRIPT = '#italy-cookie-choices-microsoft-consent-js';

    public function bothSignalsAreDeniedBeforeTheChoice(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['uet_consent_mode' => '1', 'clarity_consent_mode' => '1']);

        $I->amOnPage('/');

        $I->seeElementInDOM('head ' . self::SCRIPT);
        $I->assertEquals([['consent', 'default', ['ad_storage' => 'denied']]], $this->uetCalls($I));
        $I->assertEquals(
            [['consentv2', ['ad_Storage' => 'denied', 'analytics_Storage' => 'denied']]],
            $this->clarityCalls($I)
        );
    }

    public function acceptingEverythingGrantsBothSignals(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['uet_consent_mode' => '1', 'clarity_consent_mode' => '1']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::BANNER . ' [data-icc-action="accept-all"]');

        $uet = $this->uetCalls($I);
        $clarity = $this->clarityCalls($I);
        $I->assertEquals(['consent', 'update', ['ad_storage' => 'granted']], \end($uet));
        $I->assertEquals(
            ['consentv2', ['ad_Storage' => 'granted', 'analytics_Storage' => 'granted']],
            \end($clarity)
        );
    }

    public function theStoredConsentIsSentOnTheNextPage(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['uet_consent_mode' => '1', 'clarity_consent_mode' => '1']);
        $I->amOnPage('/');
        $I->executeJS('window.italyCookieChoices.setConsent( [ "statistics" ] );');

        $I->amOnPage('/');

        $I->assertEquals(
            [
                ['consent', 'default', ['ad_storage' => 'denied']],
                ['consent', 'update', ['ad_storage' => 'denied']],
            ],
            $this->uetCalls($I)
        );
        $I->assertEquals(
            [['consentv2', ['ad_Storage' => 'denied', 'analytics_Storage' => 'granted']]],
            $this->clarityCalls($I)
        );
    }

    public function nothingIsSentWhenBothSignalsAreOff(E2eTester $I): void
    {
        // At the current version, so the migration does not turn the signals on.
        $I->haveOptionInDatabase('italy_cookie_choices_version', 7);
        $I->haveCookieChoicesOptions(['uet_consent_mode' => null, 'clarity_consent_mode' => null]);

        $I->amOnPage('/');

        $I->dontSeeElementInDOM(self::SCRIPT);
        $I->assertNull($I->executeJS('return window.uetq ?? null;'));
        $I->assertNull($I->executeJS('return window.clarity ?? null;'));
    }

    /**
     * The calls queued in window.uetq before the UET tag loads, the browser sends the objects with the keys sorted.
     *
     * @return list<mixed>
     */
    private function uetCalls(E2eTester $I): array
    {
        $calls = $I->executeJS('return (window.uetq || []).map((call) => call);');
        $I->assertIsArray($calls);

        return \array_chunk($calls, 3);
    }

    /**
     * The calls queued in window.clarity.q before Clarity loads.
     *
     * @return list<mixed>
     */
    private function clarityCalls(E2eTester $I): array
    {
        $calls = $I->executeJS('return ((window.clarity && window.clarity.q) || []).map((call) => Array.from(call));');
        $I->assertIsArray($calls);

        return \array_values($calls);
    }
}
