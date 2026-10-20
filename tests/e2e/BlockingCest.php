<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;

final class BlockingCest
{
    private const SEP = '<---------SEP--------->';

    private const SOMETHING = '<---------SOMETHING--------->';

    private const IFRAME = '<iframe width="560" height="315" src="https://www.youtube.com/embed/icc-test"></iframe>';

    private const PLACEHOLDER = '.el';

    private const PLACEHOLDER_BUTTON = '.el button';

    private const REMOVED = '<!-- removed head from Italy Cookie Choices PHP Class -->';

    public function itBlocksEmbedsInTheContent(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['block' => '1']);
        $this->havePost($I, '<p>Video</p>' . self::IFRAME);

        $I->amOnPage('/blocked-post/');

        $I->dontSeeElementInDOM('iframe[src*="icc-test"]');
        $I->see('Content blocked', self::PLACEHOLDER);
        $I->see('Allow', self::PLACEHOLDER_BUTTON);
    }

    public function itDoesNotBlockEmbedsInTheContentByDefault(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePost($I, '<p>Video</p>' . self::IFRAME);

        $I->amOnPage('/blocked-post/');

        $I->seeElementInDOM('iframe[src*="icc-test"]');
        $I->dontSeeElementInDOM(self::PLACEHOLDER);
    }

    public function itRestoresTheBlockedEmbedOnAccept(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['block' => '1']);
        $this->havePost($I, '<p>Video</p>' . self::IFRAME);
        $I->amOnPage('/blocked-post/');

        $I->click(self::PLACEHOLDER_BUTTON);

        $I->waitForElement('iframe[src*="icc-test"]');
        $I->dontSee('Content blocked');
        $I->dontSeeElementInDOM('#cookieChoiceInfo');
        $I->assertSame(E2eTester::COOKIE_VALUE, $I->grabCookie(E2eTester::COOKIE_NAME));
    }

    public function itDoesNotBlockTheContentOnceAccepted(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['block' => '1']);
        $this->havePost($I, '<p>Video</p>' . self::IFRAME);
        $I->amOnPage('/');
        $I->setCookie(E2eTester::COOKIE_NAME, E2eTester::COOKIE_VALUE);

        $I->amOnPage('/blocked-post/');

        $I->seeElementInDOM('iframe[src*="icc-test"]');
        $I->dontSeeElementInDOM(self::PLACEHOLDER);
    }

    public function itBlocksEmbedsInTheWidgets(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['widget_block' => '1']);
        $this->haveTextWidget($I, self::IFRAME);

        $I->amOnPage('/');

        $I->see('Widget video', '.widget');
        $I->dontSeeElementInDOM('.widget iframe[src*="icc-test"]');
        $I->see('Content blocked', '.widget ' . self::PLACEHOLDER);
    }

    public function itRestoresTheBlockedWidgetOnAccept(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['widget_block' => '1']);
        $this->haveTextWidget($I, self::IFRAME);
        $I->amOnPage('/');

        $I->click('.widget ' . self::PLACEHOLDER_BUTTON);

        $I->waitForElement('.widget iframe[src*="icc-test"]');
    }

    public function itDoesNotBlockTheWidgetsByDefault(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->haveTextWidget($I, self::IFRAME);

        $I->amOnPage('/');

        $I->seeElementInDOM('.widget iframe[src*="icc-test"]');
    }

    public function itBlocksScriptsInTheBodyUntilAccepted(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['all_block' => '1']);
        $this->havePost($I, '<script>window.iccBodyScript = "ran";</script><p>Body content</p>');
        $I->amOnPage('/blocked-post/');

        $I->assertNull($I->executeJS('return window.iccBodyScript;'));
        $I->seeInPageSource('<!-- ICCStartBody -->');
        $I->see('Body content');

        $I->click('.entry-content ' . self::PLACEHOLDER_BUTTON);

        $I->waitForJS('return window.iccBodyScript === "ran";');
    }

    public function itKeepsTheAllowedScriptsInTheBody(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions(['all_block' => '1', 'allow_script' => ['iccAllowedScript']]);
        $this->havePost(
            $I,
            '<script>window.iccAllowedScript = "ran";</script><script>window.iccBlockedScript = "ran";</script>'
        );

        $I->amOnPage('/blocked-post/');

        $I->assertSame('ran', $I->executeJS('return window.iccAllowedScript;'));
        $I->assertNull($I->executeJS('return window.iccBlockedScript;'));
    }

    public function itKeepsTheCustomExcludedScriptsInTheBody(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([
            'all_block' => '1',
            'custom_script_block_body_exclude' => '<script>window.iccAllowedScript' . self::SOMETHING . '</script>',
        ]);
        $this->havePost(
            $I,
            '<script>window.iccAllowedScript = "ran";</script><script>window.iccBlockedScript = "ran";</script>'
        );

        $I->amOnPage('/blocked-post/');

        $I->assertSame('ran', $I->executeJS('return window.iccAllowedScript;'));
        $I->assertNull($I->executeJS('return window.iccBlockedScript;'));
    }

    public function itRemovesTheCustomScriptsFromHeadAndFooter(E2eTester $I): void
    {
        $I->haveOptionInDatabase('icc_e2e_scripts', '1');
        $I->haveCookieChoicesOptions([
            'all_block' => '1',
            'custom_script_block' => '<script id="icc-head-tracker">window.iccHeadTracker = true;</script>'
                . "\n" . self::SEP . "\n"
                . '<script id="icc-footer-tracker"' . self::SOMETHING . '</script>',
        ]);

        $I->amOnPage('/');

        $I->waitForElementVisible('#cookieChoiceInfo');
        $I->dontSeeElementInDOM('#icc-head-tracker');
        $I->dontSeeElementInDOM('#icc-footer-tracker');
        $I->assertNull($I->executeJS('return window.iccHeadTracker;'));
        $I->assertNull($I->executeJS('return window.iccFooterTracker;'));
        $I->seeInPageSource('<!-- ICCEndHead -->');
        $I->seeInPageSource('<!-- ICCStartFooter -->');
        $I->seeInPageSource(self::REMOVED);
    }

    public function itLoadsTheCustomScriptsOnceAccepted(E2eTester $I): void
    {
        $I->haveOptionInDatabase('icc_e2e_scripts', '1');
        $I->haveCookieChoicesOptions([
            'all_block' => '1',
            'custom_script_block' => '<script id="icc-head-tracker">window.iccHeadTracker = true;</script>',
        ]);
        $I->amOnPage('/');
        $I->waitForElementVisible('#cookieChoiceInfo');
        $I->click('#cookieChoiceDismiss');

        $I->amOnPage('/');

        $I->assertTrue($I->executeJS('return window.iccHeadTracker;'));
        $I->dontSeeInPageSource('<!-- ICCEndHead -->');
    }

    public function itDoesNotRemoveTheCustomScriptsWithoutAllBlock(E2eTester $I): void
    {
        $I->haveOptionInDatabase('icc_e2e_scripts', '1');
        $I->haveCookieChoicesOptions([
            'custom_script_block' => '<script id="icc-head-tracker">window.iccHeadTracker = true;</script>',
        ]);

        $I->amOnPage('/');

        $I->assertTrue($I->executeJS('return window.iccHeadTracker;'));
        $I->dontSeeInPageSource(self::REMOVED);
    }

    private function havePost(E2eTester $I, string $content): void
    {
        $I->havePostInDatabase([
            'post_name' => 'blocked-post',
            'post_title' => 'Blocked post',
            'post_content' => $content,
        ]);
    }

    private function haveTextWidget(E2eTester $I, string $text): void
    {
        $I->haveOptionInDatabase('widget_text', [
            2 => ['title' => 'Widget video', 'text' => $text, 'filter' => false, 'visual' => false],
            '_multiwidget' => 1,
        ]);
        $I->haveOptionInDatabase('sidebars_widgets', [
            'wp_inactive_widgets' => [],
            'sidebar-1' => ['text-2'],
            'array_version' => 3,
        ]);
    }
}
