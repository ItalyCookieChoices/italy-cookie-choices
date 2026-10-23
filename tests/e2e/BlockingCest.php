<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\E2e;

use E2eTester;
use Facebook\WebDriver\Remote\RemoteWebDriver;

final class BlockingCest
{
    private const IFRAME = '<iframe width="560" height="315" src="https://www.youtube.com/embed/icc-test"></iframe>';

    private const BLOCKED_IFRAME = 'iframe[data-icc-src*="icc-test"][data-category="marketing"]:not([src])';

    private const LOADED_IFRAME = 'iframe[src*="icc-test"]';

    private const PLACEHOLDER = '.icc-placeholder';

    public function itBlocksTheVendorsInTheContentByDefault(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePost($I, '<p>Video</p>' . self::IFRAME);

        $I->amOnPage('/blocked-post/');

        $I->seeElementInDOM(self::BLOCKED_IFRAME);
        $I->dontSeeElement(self::LOADED_IFRAME);
        $I->see('Content blocked', '.entry-content ' . self::PLACEHOLDER);
        $I->see('Allow', '.entry-content ' . self::PLACEHOLDER . ' [data-icc-accept="marketing"]');
    }

    public function thePlaceholderLoadsTheFrameWithItsCategory(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePost($I, '<p>Video</p>' . self::IFRAME);
        $I->amOnPage('/blocked-post/');

        $I->click(self::PLACEHOLDER . ' [data-icc-accept="marketing"]');

        $I->waitForElementVisible(self::LOADED_IFRAME);
        $I->dontSeeElementInDOM(self::PLACEHOLDER);
        $I->dontSeeElement('#icc-banner');
        $I->assertSame(['necessary', 'marketing'], $this->consentCategories($I));
    }

    public function thePlaceholderOpensThePreferences(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePost($I, '<p>Video</p>' . self::IFRAME);
        $I->amOnPage('/blocked-post/');
        $I->waitForElementVisible('#icc-banner');

        $I->click(self::PLACEHOLDER . ' .icc-placeholder__preferences');

        $I->waitForElementVisible('#icc-preferences');
    }

    public function theFramesAreLoadedOnceAccepted(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePost($I, '<p>Video</p>' . self::IFRAME);
        $I->amOnPage('/');
        $I->executeJS('window.italyCookieChoices.acceptAll();');

        $I->amOnPage('/blocked-post/');

        $I->seeElement(self::LOADED_IFRAME);
        $I->dontSeeElementInDOM(self::PLACEHOLDER);
    }

    public function anotherCategoryDoesNotLoadTheFrame(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePost($I, '<p>Video</p>' . self::IFRAME);
        $I->amOnPage('/');
        $I->executeJS('window.italyCookieChoices.setConsent(["statistics"]);');

        $I->amOnPage('/blocked-post/');

        $I->seeElementInDOM(self::BLOCKED_IFRAME);
        $I->seeElement(self::PLACEHOLDER);
    }

    public function itBlocksTheVendorsInTheWidgets(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->haveTextWidget($I, self::IFRAME);

        $I->amOnPage('/');

        $I->see('Widget video', '.widget');
        $I->seeElementInDOM('.widget ' . self::BLOCKED_IFRAME);

        $I->click('.widget ' . self::PLACEHOLDER . ' [data-icc-accept]');

        $I->waitForElementVisible('.widget ' . self::LOADED_IFRAME);
    }

    public function itBlocksTheEmbeds(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePost($I, "<p>Video</p>\n\nhttps://www.youtube.com/watch?v=icc-oembed\n\n<p>After</p>");

        $I->amOnPage('/blocked-post/');

        $I->seeElementInDOM('iframe[data-icc-src*="icc-oembed"]:not([src])');
        $I->seeElement('.entry-content ' . self::PLACEHOLDER);
    }

    public function theAuthorBlocksAFrameByHand(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePost($I, '<iframe data-category="statistics" src="/?icc-frame=1"></iframe>');

        $I->amOnPage('/blocked-post/');

        $I->seeElementInDOM('iframe[data-icc-src="/?icc-frame=1"]:not([src])');
        $I->seeElement('.entry-content ' . self::PLACEHOLDER . ' [data-icc-accept="statistics"]');
    }

    public function theAllowedFramesAreNeverBlocked(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([
            'allowed_patterns' => ['youtube.com/embed/icc-test'],
        ]);
        $this->havePost($I, self::IFRAME);

        $I->amOnPage('/blocked-post/');

        $I->seeElement(self::LOADED_IFRAME);
        $I->dontSeeElementInDOM(self::PLACEHOLDER);
    }

    public function theFeedsAreNeverBlocked(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePost($I, '<p>Video</p>' . self::IFRAME);
        $I->amOnPage('/');

        $feed = $I->executeInSelenium(static fn(RemoteWebDriver $driver): mixed => $driver->executeAsyncScript(
            'const done = arguments[arguments.length - 1];'
            . 'fetch("/feed/").then((response) => response.text()).then(done);'
        ));

        $I->assertIsString($feed);
        $I->assertStringContainsString('src="https://www.youtube.com/embed/icc-test"', $feed);
        $I->assertStringNotContainsString('data-icc-src', $feed);
    }

    public function itRunsTheEnqueuedScriptsAfterTheConsent(E2eTester $I): void
    {
        $I->haveOptionInDatabase('icc_e2e_enqueued', '1');
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');

        $I->seeElementInDOM('script#icc-e2e-tracker-js[type="text/plain"][data-category="statistics"]');
        $I->seeElementInDOM('script#icc-e2e-tracker-js-before[type="text/plain"][data-category="statistics"]');
        $I->assertNull($I->executeJS('return window.iccTracker;'));

        $I->executeJS('window.italyCookieChoices.setConsent(["statistics"]);');

        $I->waitForJS('return Array.isArray(window.iccTracker);');
        $I->assertSame(['inline', 'file'], $I->executeJS('return window.iccTracker;'));
        $I->assertNull($I->executeJS('return window.iccTagged;'));
    }

    public function aScriptIsBlockedByItsCategory(E2eTester $I): void
    {
        $I->haveOptionInDatabase('icc_e2e_enqueued', '1');
        $I->haveCookieChoicesOptions([]);
        $I->amOnPage('/');
        $I->executeJS('window.italyCookieChoices.setConsent(["preferences"]);');

        $I->amOnPage('/');

        $I->waitForJS('return window.iccTagged === "ran";');
        $I->assertNull($I->executeJS('return window.iccTracker;'));
    }

    public function theThemeScriptsAreBlockedOnlyInTheCompatibilityMode(E2eTester $I): void
    {
        $I->haveOptionInDatabase('icc_e2e_scripts', '1');
        $I->haveCookieChoicesOptions(['custom_vendors' => [$this->trackers(['iccHeadTracker'])]]);

        $I->amOnPage('/');

        $I->assertTrue($I->executeJS('return window.iccHeadTracker;'));
    }

    public function theCompatibilityModeBlocksTheWholePage(E2eTester $I): void
    {
        $I->haveOptionInDatabase('icc_e2e_scripts', '1');
        $I->haveCookieChoicesOptions([
            'all_block' => '1',
            'custom_vendors' => [$this->trackers(['iccHeadTracker', 'iccFooterTracker'])],
        ]);
        $I->amOnPage('/');

        $I->seeElementInDOM('script#icc-head-tracker[type="text/plain"][data-category="marketing"]');
        $I->seeElementInDOM('script#icc-footer-tracker[type="text/plain"][data-category="marketing"]');
        $I->assertNull($I->executeJS('return window.iccHeadTracker;'));
        $I->waitForElementVisible('#icc-banner');

        $I->click('#icc-banner [data-icc-action="accept-all"]');

        $I->waitForJS('return window.iccHeadTracker === true && window.iccFooterTracker === true;');
    }

    /**
     * The lists of the 2.x versions become a custom vendor on the first request after the update.
     */
    public function theListsOfThe2xVersionsStillBlock(E2eTester $I): void
    {
        $I->haveOptionInDatabase('icc_e2e_scripts', '1');
        $I->haveCookieChoicesOptions([
            'all_block' => '1',
            'block_script' => ['iccHeadTracker' => 'iccHeadTracker'],
            'allow_script' => ['iccFooterTracker' => 'iccFooterTracker'],
        ]);

        $I->amOnPage('/');

        $I->assertNull($I->executeJS('return window.iccHeadTracker;'));
        $I->assertTrue($I->executeJS('return window.iccFooterTracker;'));
    }

    public function theCompatibilityModeKeepsTheAllowedScripts(E2eTester $I): void
    {
        $I->haveOptionInDatabase('icc_e2e_scripts', '1');
        $I->haveCookieChoicesOptions([
            'all_block' => '1',
            'custom_vendors' => [$this->trackers(['Tracker'])],
            'allowed_patterns' => ['iccFooterTracker'],
        ]);

        $I->amOnPage('/');

        $I->assertNull($I->executeJS('return window.iccHeadTracker;'));
        $I->assertTrue($I->executeJS('return window.iccFooterTracker;'));
    }

    public function thePlaceholderHasNoAccessibilityViolations(E2eTester $I): void
    {
        $I->haveCookieChoicesOptions([]);
        $this->havePost($I, '<p>Video</p>' . self::IFRAME);
        $I->amOnPage('/blocked-post/');
        $I->seeElement(self::PLACEHOLDER);

        $I->assertSame([], $I->grabAccessibilityViolations(self::PLACEHOLDER));
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

    private function consentCategories(E2eTester $I): mixed
    {
        return $I->executeJS('return window.italyCookieChoices.getConsent().categories;');
    }

    /**
     * @param list<string> $patterns
     * @return array{id: string, name: string, category: string, patterns: list<string>}
     */
    private function trackers(array $patterns): array
    {
        return ['id' => 'trackers', 'name' => 'Trackers', 'category' => 'marketing', 'patterns' => $patterns];
    }
}
