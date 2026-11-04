<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;

/**
 * The integration with the WP Consent API plugin, installed in the WordPress image.
 */
final class WpConsentApiCest
{
    private const BANNER = '#icc-banner';
    private const PLUGINS = [
        'italy-cookie-choices/italy-cookie-choices.php',
        'wp-consent-api/wp-consent-api.php',
    ];
    private const DENIED = [
        'functional' => true,
        'preferences' => false,
        'statistics' => false,
        'statistics-anonymous' => false,
        'marketing' => false,
    ];
    private const ALLOWED = [
        'functional' => true,
        'preferences' => true,
        'statistics' => true,
        'statistics-anonymous' => true,
        'marketing' => true,
    ];

    public function onlyTheFunctionalCategoryIsAllowedBeforeTheChoice(E2eTester $I): void
    {
        $this->activateTheWpConsentApi($I);
        $I->haveCookieChoicesOptions([]);

        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->assertSame('optin', $I->executeJS('return window.wp_consent_type;'));
        $I->assertEquals(self::DENIED, $this->browserConsent($I));
        $I->assertEquals(self::DENIED, $this->serverConsent($I));
    }

    public function acceptingEverythingAllowsEveryCategory(E2eTester $I): void
    {
        $this->activateTheWpConsentApi($I);
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::BANNER . ' [data-icc-action="accept-all"]');

        $I->assertEquals(self::ALLOWED, $this->browserConsent($I));
        $I->amOnPage('/');
        $I->assertEquals(self::ALLOWED, $this->serverConsent($I));
    }

    public function theAcceptedCategoriesAreAllowed(E2eTester $I): void
    {
        $this->activateTheWpConsentApi($I);
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');

        $I->executeJS('window.italyCookieChoices.setConsent( [ "statistics" ] );');

        $expected = [...self::DENIED, 'statistics' => true, 'statistics-anonymous' => true];
        $I->assertEquals($expected, $this->browserConsent($I));
        $I->amOnPage('/');
        $I->assertEquals($expected, $this->serverConsent($I));
    }

    public function aWithdrawnConsentIsDenied(E2eTester $I): void
    {
        $this->activateTheWpConsentApi($I);
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->executeJS('window.italyCookieChoices.acceptAll();');

        $I->executeJS('window.italyCookieChoices.withdraw();');

        $I->assertEquals(self::DENIED, $this->browserConsent($I));
        $I->amOnPage('/');
        $I->assertEquals(self::DENIED, $this->serverConsent($I));
    }

    public function theApiIsNotAnsweredWhenTheBannerIsOff(E2eTester $I): void
    {
        $this->activateTheWpConsentApi($I);
        $I->haveCookieChoicesOptions(['active' => null]);

        $I->amOnPage('/');

        $I->dontSeeElementInDOM('#italy-cookie-choices-wp-consent-api-js');
        $I->assertEquals(self::ALLOWED, $this->serverConsent($I));
    }

    private function activateTheWpConsentApi(E2eTester $I): void
    {
        $I->haveOptionInDatabase('active_plugins', self::PLUGINS);
        $I->haveOptionInDatabase('icc_e2e_wp_consent_api', '1');
    }

    /**
     * The answers of wp_has_consent() in JavaScript, the browser sends the object with the keys sorted.
     *
     * @return array<string, bool>
     */
    private function browserConsent(E2eTester $I): array
    {
        $consent = $I->executeJS(
            'return Object.fromEntries('
            . \json_encode(\array_keys(self::DENIED), \JSON_THROW_ON_ERROR)
            . '.map((category) => [category, window.wp_has_consent(category)]));'
        );
        $I->assertIsArray($consent);

        /** @var array<string, bool> $consent */
        return $consent;
    }

    /**
     * The answers of wp_has_consent() in PHP, printed by the e2e fixtures.
     *
     * @return array<string, bool>
     */
    private function serverConsent(E2eTester $I): array
    {
        $json = $I->executeJS('return document.getElementById("icc-e2e-wp-has-consent")?.textContent ?? "";');
        $I->assertIsString($json);
        $consent = \json_decode($json, true);
        $I->assertIsArray($consent);

        /** @var array<string, bool> $consent */
        return $consent;
    }
}
