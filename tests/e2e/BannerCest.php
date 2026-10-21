<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;

final class BannerCest
{
    private const BANNER = '#cookieChoiceInfo';

    private const ACCEPT = '#cookieChoiceDismiss';

    public function itDoesNotShowTheBannerWhenNotActive(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['active' => null]);

        $I->amOnPage('/');

        $I->dontSeeElementInDOM(self::BANNER);
        $I->dontSeeInPageSource('<!-- Italy Cookie Choices -->');
    }

    public function itShowsTheTopBar(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['text' => 'We use <strong>cookies</strong>']);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->see('We use cookies', self::BANNER . ' .consentText');
        $I->seeElement(self::BANNER . ' .consentText strong');
        $I->seeElement(self::BANNER . ' a.italybtn', ['href' => 'http://wordpress/privacy-policy/']);
        $I->see('Read more', self::BANNER . ' a.italybtn');
        $I->see('Accept', self::ACCEPT);
        $I->dontSeeElement(self::BANNER . ' .glassStyle');
        $I->assertSame('0px', $this->bannerStyle($I, 'top'));
        $I->assertSame('fixed', $this->bannerStyle($I, 'position'));
    }

    public function itOpensThePolicyInTheSameTabByDefault(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->dontSeeElement(self::BANNER . ' a.italybtn[target]');
    }

    public function itOpensThePolicyInANewTab(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['target' => '1']);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->seeElement(self::BANNER . ' a.italybtn', ['target' => '_blank']);
    }

    public function itShowsTheDialog(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['banner' => '2']);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER . ' .bannerStyle');
        $I->seeElement(self::BANNER . ' .glassStyle');
        $I->see('This site uses cookies', self::BANNER . ' .contentStyle .consentText');
    }

    public function itShowsTheBottomBar(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['banner' => '3']);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->assertSame('0px', $this->bannerStyle($I, 'bottom'));
    }

    public function itUsesTheCustomClasses(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([
            'bannerStyle' => 'my-banner',
            'contentStyle' => 'my-content',
            'consentText' => 'my-text',
            'infoClass' => 'my-info',
            'closeClass' => 'my-close',
        ]);

        $I->amOnPage('/');

        $I->waitForElementVisible(['css' => self::BANNER . '.my-banner']);
        $I->seeElement(self::BANNER . ' .my-content .my-text');
        $I->seeElement(self::BANNER . ' a.my-info');
        $I->seeElement(['css' => self::ACCEPT . '.my-close']);
    }

    public function itUsesTheBannerColors(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['banner_bg' => '#ff0000', 'banner_text_color' => '#00ff00']);

        $I->amOnPage('/');

        $I->waitForElementVisible(self::BANNER);
        $I->assertSame('rgb(255, 0, 0)', $this->bannerStyle($I, 'background-color'));
        $I->assertSame('rgb(0, 255, 0)', $this->bannerStyle($I, 'color'));
    }

    public function itAcceptsTheCookiesWithTheButton(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::ACCEPT);

        $I->dontSeeElementInDOM(self::BANNER);
        $I->seeCookie(E2eTester::COOKIE_NAME);
        $I->assertSame(E2eTester::COOKIE_VALUE, $I->grabCookie(E2eTester::COOKIE_NAME));
    }

    public function itDoesNotPrintTheBannerOnceAccepted(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);
        $I->click(self::ACCEPT);

        $I->amOnPage('/');

        $I->dontSeeElementInDOM(self::BANNER);
        $I->dontSeeInPageSource('<!-- Italy Cookie Choices -->');
    }

    public function itUsesTheCustomCookieNameAndValue(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['cookie_name' => 'myConsent', 'cookie_value' => 'accepted']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click(self::ACCEPT);

        $I->assertSame('accepted', $I->grabCookie('myConsent'));
        $I->dontSeeCookie(E2eTester::COOKIE_NAME);
    }

    public function itDoesNotShowTheBannerWithTheConsentCookie(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->setCookie(E2eTester::COOKIE_NAME, E2eTester::COOKIE_VALUE);

        $I->amOnPage('/');

        $I->dontSeeElementInDOM(self::BANNER);
        $I->dontSeeInPageSource('<!-- Italy Cookie Choices -->');
    }

    public function itAddsTheTopMarginToTheHtmlUntilAccepted(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['html_margin' => '1']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->assertNotSame('', $I->executeJS('return document.documentElement.style.marginTop;'));

        $I->click(self::ACCEPT);

        $I->assertSame('', $I->executeJS('return document.documentElement.style.marginTop;'));
    }

    public function itReloadsThePageOnAccept(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['reload' => '1']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);
        $I->executeJS('window.iccBeforeReload = true;');

        $I->click(self::ACCEPT);

        $I->waitForJS('return document.readyState === "complete" && window.iccBeforeReload === undefined;');
        $I->dontSeeInPageSource('<!-- Italy Cookie Choices -->');
    }

    public function itAcceptsTheCookiesOnScroll(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['scroll' => '1']);
        $I->havePostInDatabase([
            'post_name' => 'long-post',
            'post_title' => 'Long post',
            'post_content' => \str_repeat('<p>Lorem ipsum dolor sit amet.</p>', 300),
        ]);
        $I->amOnPage('/long-post/');
        $I->waitForElementVisible(self::BANNER);

        $I->scrollTo('footer');

        $I->waitForElementNotVisible(self::BANNER);
        $I->assertSame(E2eTester::COOKIE_VALUE, $I->grabCookie(E2eTester::COOKIE_NAME));
    }

    public function itDoesNotAcceptTheCookiesOnScrollByDefault(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->havePostInDatabase([
            'post_name' => 'long-post',
            'post_title' => 'Long post',
            'post_content' => \str_repeat('<p>Lorem ipsum dolor sit amet.</p>', 300),
        ]);
        $I->amOnPage('/long-post/');
        $I->waitForElementVisible(self::BANNER);

        $I->scrollTo('footer');

        $I->seeElement(self::BANNER);
        $I->dontSeeCookie(E2eTester::COOKIE_NAME);
    }

    public function itAcceptsTheCookiesOnTheSecondPageView(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['secondView' => '1']);
        $I->havePostInDatabase(['post_name' => 'second-page', 'post_title' => 'Second page']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click('Second page');

        $I->waitForJS('return location.pathname === "/second-page/" && document.readyState === "complete";', 15);
        $I->dontSeeElementInDOM(self::BANNER);
        $I->assertSame(E2eTester::COOKIE_VALUE, $I->grabCookie(E2eTester::COOKIE_NAME));
    }

    public function itDoesNotAcceptTheCookiesOnTheSecondPageViewByDefault(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $I->havePostInDatabase(['post_name' => 'second-page', 'post_title' => 'Second page']);
        $I->amOnPage('/');
        $I->waitForElementVisible(self::BANNER);

        $I->click('Second page');

        $I->waitForJS('return location.pathname === "/second-page/" && document.readyState === "complete";', 15);
        $I->waitForElementVisible(self::BANNER);
        $I->dontSeeCookie(E2eTester::COOKIE_NAME);
    }

    public function itDoesNotShowTheBannerOnTheLoginPage(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);

        $I->amOnPage('/wp-login.php');

        $I->dontSeeInPageSource('<!-- Italy Cookie Choices -->');
    }

    public function itDoesNotShowTheBannerOnSitemaps(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);

        $I->amOnPage('/wp-sitemap.xml');

        $I->dontSeeInPageSource('Italy Cookie Choices -->');
        $I->dontSeeInPageSource('cookieChoices');
    }

    public function itDoesNotShowTheBannerInTheAdmin(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);

        $I->loginAsAdmin();
        $I->amOnAdminPage('/');

        $I->dontSeeInPageSource('<!-- Italy Cookie Choices -->');
    }

    private function bannerStyle(E2eTester $I, string $property): string
    {
        return (string) $I->executeJS(
            'return getComputedStyle(document.querySelector(arguments[0])).getPropertyValue(arguments[1]);',
            [self::BANNER, $property]
        );
    }
}
