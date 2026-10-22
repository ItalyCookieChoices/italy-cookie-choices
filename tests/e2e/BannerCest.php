<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;
use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * The consent banner of #235: accept all, reject all and preferences with the same weight.
 */
final class BannerCest
{
    private const BANNER = '#icc-banner';

    private const PREFERENCES = '#icc-preferences';

    private const ACCEPT = '#icc-banner [data-icc-action="accept-all"]';

    private const REJECT = '#icc-banner [data-icc-action="reject-all"]';

    private const OPEN_PREFERENCES = '#icc-banner [data-icc-action="preferences"]';

    private const CLOSE = '#icc-banner [data-icc-action="close"]';

    private const LINK = '#icc-banner .icc-banner__link';

    private const ALL_CATEGORIES = ['necessary', 'preferences', 'statistics', 'marketing'];

    public function itDoesNotPrintTheBannerWhenNotActive(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['active' => null]);

        $I->amOnPage('/');

        $I->dontSeeElementInDOM(self::BANNER);
    }

    public function itShowsTheTopBarWithThreeChoicesOfTheSameWeight(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['text' => 'We use <strong>cookies</strong>']);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->seeElement(['css' => self::BANNER . '.icc-banner--top']);
        $I->see('We use cookies', '#icc-banner-text');
        $I->seeElement('#icc-banner-text strong');
        $I->seeElement(self::LINK, ['href' => 'http://wordpress/privacy-policy/']);
        $I->see('Read more', self::LINK);
        $I->see('Accept', self::ACCEPT);
        $I->see('Reject all', self::REJECT);
        $I->see('Preferences', self::OPEN_PREFERENCES);
        $I->assertSame(
            1,
            $I->executeJS(
                'const buttons = document.querySelectorAll("#icc-banner .icc-banner__actions button");'
                . 'const styles = Array.from(buttons, (button) => {'
                . 'const style = getComputedStyle(button);'
                . 'return [style.fontSize, style.fontWeight, style.backgroundColor, style.borderStyle].join();'
                . '});'
                . 'return new Set(styles).size;'
            )
        );
    }

    public function itOpensThePolicyInTheSameTabByDefault(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->dontSeeElement(self::LINK . '[target]');
        $I->dontSeeElement(self::LINK . '[rel]');
    }

    public function itOpensThePolicyInANewTab(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['target' => '1']);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->seeElement(self::LINK, ['target' => '_blank', 'rel' => 'noopener']);
    }

    public function itAddsNofollowToThePolicyLink(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['target' => '1', 'nofollow' => '1']);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->seeElement(self::LINK, ['rel' => 'noopener nofollow']);
    }

    public function itShowsTheDialog(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['banner' => '2']);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->seeElement(['css' => self::BANNER . '.icc-banner--modal'], ['aria-modal' => 'true']);
    }

    public function itShowsTheBottomBar(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['banner' => '3']);

        $I->amOnPage('/');

        $I->waitForElementVisible(['css' => self::BANNER . '.icc-banner--bottom']);
    }

    public function itUsesTheCustomClasses(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([
            'bannerStyle' => 'my-banner',
            'contentStyle' => 'my-content',
            'consentText' => 'my-text',
            'infoClass' => 'my-info',
            'closeClass' => 'my-button',
        ]);

        $I->amOnPage('/');

        $I->waitForElementVisible(['css' => self::BANNER . '.my-banner']);
        $I->seeElement(self::BANNER . ' .my-content .my-text');
        $I->seeElement(self::BANNER . ' a.my-info');
        $I->seeElement(self::ACCEPT . '.my-button');
    }

    public function itUsesTheBannerColors(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['banner_bg' => '#123456', 'banner_text_color' => '#abcdef']);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->assertSame(
            ['rgb(18, 52, 86)', 'rgb(171, 205, 239)'],
            $I->executeJS(
                'const style = getComputedStyle(document.getElementById("icc-banner"));'
                . 'return [style.backgroundColor, style.color];'
            )
        );
    }

    public function theCustomTemplateUsesOnlyTheCustomCss(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([
            'js_template' => 'custom',
            'customCSS' => '#icc-banner{border-top:7px solid red}',
        ]);

        $I->amOnPage('/');

        $I->dontSeeElementInDOM('link#italy-cookie-choices-banner-css');
        $I->seeInPageSource('#icc-banner{border-top:7px solid red}');
    }

    public function itAcceptsAllTheCategories(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::ACCEPT);

        $I->waitForElementNotVisible(self::BANNER);
        $I->assertSame(self::ALL_CATEGORIES, $this->consentCategories($I));
        $I->assertSame(E2eTester::COOKIE_VALUE, $I->grabCookie(E2eTester::COOKIE_NAME));
    }

    public function itRejectsAllTheOptionalCategories(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::REJECT);

        $I->waitForElementNotVisible(self::BANNER);
        $I->assertSame(['necessary'], $this->consentCategories($I));
        $I->dontSeeCookie(E2eTester::COOKIE_NAME);
    }

    public function closingTheBannerKeepsOnlyTheNecessaryCookies(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::CLOSE);

        $I->waitForElementNotVisible(self::BANNER);
        $I->assertSame(['necessary'], $this->consentCategories($I));
    }

    public function escapeKeepsOnlyTheNecessaryCookies(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);
        $I->assertSame('icc-banner', $I->executeJS('return document.activeElement.id;'));

        $I->pressKey(self::BANNER, \Facebook\WebDriver\WebDriverKeys::ESCAPE);

        $I->waitForElementNotVisible(self::BANNER);
        $I->assertSame(['necessary'], $this->consentCategories($I));
    }

    public function itSavesTheChoiceByCategory(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::OPEN_PREFERENCES);
        $I->waitForElementVisible(self::PREFERENCES);
        $I->seeElement(self::OPEN_PREFERENCES, ['aria-expanded' => 'true']);
        $I->seeCheckboxIsChecked('#icc-category-necessary');
        $I->seeElement('#icc-category-necessary', ['disabled' => 'true']);
        $I->assertSame('icc-category-preferences', $I->executeJS('return document.activeElement.id;'));
        $I->checkOption('#icc-category-statistics');
        $I->click('Save preferences', self::PREFERENCES);

        $I->waitForElementNotVisible(self::PREFERENCES);
        $I->dontSeeElement(self::BANNER);
        $I->assertSame(['necessary', 'statistics'], $this->consentCategories($I));
        $I->dontSeeCookie(E2eTester::COOKIE_NAME);
    }

    public function escapeClosesThePreferencesAndGivesTheFocusBack(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);
        $I->click(self::OPEN_PREFERENCES);
        $I->waitForElementVisible(self::PREFERENCES);

        $I->pressKey('#icc-category-preferences', \Facebook\WebDriver\WebDriverKeys::ESCAPE);

        $I->waitForElementNotVisible(self::PREFERENCES);
        $I->seeElement(self::BANNER);
        $I->assertSame('preferences', $I->executeJS('return document.activeElement.dataset.iccAction;'));
    }

    public function itDoesNotShowTheBannerOnceChosen(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);
        $I->click(self::REJECT);

        $I->amOnPage('/');

        $I->seeElementInDOM(self::BANNER);
        $I->dontSeeElement(self::BANNER);
    }

    public function itAddsTheTopMarginToTheHtmlUntilChosen(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['html_margin' => '1']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->assertNotSame('0px', $I->executeJS('return getComputedStyle(document.documentElement).marginTop;'));

        $I->click(self::ACCEPT);

        $I->assertSame('0px', $I->executeJS('return getComputedStyle(document.documentElement).marginTop;'));
    }

    public function itReloadsThePageAfterAChoice(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['reload' => '1']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);
        $I->executeJS('window.iccBeforeReload = true;');

        $I->click(self::ACCEPT);

        $I->waitForJS('return document.readyState === "complete" && window.iccBeforeReload === undefined;');
        $I->dontSeeElement(self::BANNER);
    }

    public function scrollingDoesNotGiveTheConsent(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['scroll' => '1']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->executeJS('window.scrollTo(0, document.body.scrollHeight); window.dispatchEvent(new Event("scroll"));');

        $I->seeElement(self::BANNER);
        $I->assertNull($I->executeJS('return window.italyCookieChoices.getConsent();'));
    }

    public function theSecondPageViewDoesNotGiveTheConsent(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['secondView' => '1']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::LINK);
        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->dontSeeCookie(E2eTester::COOKIE_NAME);
    }

    public function itIsNotShownOnThePolicyPage(E2eTester $I): void
    {
        $I->havePageInDatabase(['post_name' => 'cookie-policy', 'post_title' => 'Cookie policy']);
        $I->haveCookieChoicesOptions(['slug' => 'cookie-policy']);

        $I->amOnPage('/cookie-policy/');

        $I->dontSeeElementInDOM(self::BANNER);
    }

    public function itIsNotShownOnTheExcludedPages(E2eTester $I): void
    {
        $id = $I->havePageInDatabase(['post_name' => 'contact', 'post_title' => 'Contact']);
        $I->havePageInDatabase(['post_name' => 'about', 'post_title' => 'About']);
        $I->haveCookieChoicesOptions(['excluded_pages' => $id . ', about']);

        $I->amOnPage('/contact/');
        $I->dontSeeElementInDOM(self::BANNER);

        $I->amOnPage('/about/');
        $I->dontSeeElementInDOM(self::BANNER);

        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);
    }

    public function theChoiceClosesTheBannerInTheOtherTabs(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);
        $I->executeJS('window.open("/", "other");');
        $I->switchToNextTab();
        $I->waitForElementVisible(self::BANNER);
        $I->switchToPreviousTab();

        $I->click(self::ACCEPT);
        $I->switchToNextTab();

        $I->waitForElementNotVisible(self::BANNER);
        $I->assertSame(self::ALL_CATEGORIES, $this->consentCategories($I));
        $I->closeTab();
    }

    public function theBannerHasNoAccessibilityViolations(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->assertSame([], $this->accessibilityViolations($I, self::BANNER));

        $I->click(self::OPEN_PREFERENCES);
        $I->waitForElementVisible(self::PREFERENCES);

        $I->assertSame([], $this->accessibilityViolations($I, self::PREFERENCES));
    }

    /**
     * @return mixed The categories of the consent in the browser.
     */
    private function consentCategories(E2eTester $I): mixed
    {
        return $I->executeJS('return window.italyCookieChoices.getConsent().categories;');
    }

    /**
     * Runs axe-core on the element and returns the ids of the violated rules.
     *
     * @return mixed
     */
    private function accessibilityViolations(E2eTester $I, string $selector): mixed
    {
        $root = \codecept_root_dir();
        $I->assertIsString($root);
        $axe = \file_get_contents($root . 'node_modules/axe-core/axe.min.js');
        $I->assertIsString($axe);
        $I->executeJS($axe);

        return $I->executeInSelenium(static fn(RemoteWebDriver $driver): mixed => $driver->executeAsyncScript(
            'const done = arguments[arguments.length - 1];'
            . 'axe.run(arguments[0], {runOnly: ["wcag2a", "wcag2aa", "wcag21a", "wcag21aa", "best-practice"]})'
            . '.then((results) => done(results.violations.map((violation) => violation.id)));',
            [$selector]
        ));
    }
}
