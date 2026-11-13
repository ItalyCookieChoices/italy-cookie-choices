<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Multilingual;

use ItalyCookieChoices\Multilingual\Application\PolicySuggestion;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\Multilingual\FakeConnectedSites;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class PolicySuggestionTest extends WPTestCase
{
    private int $italian = 0;
    private int $german = 0;
    private FakeConnectedSites $sites;

    public function setUp(): void
    {
        parent::setUp();
        $this->italian = $this->site();
        $this->german = $this->site();
        $this->sites = new FakeConnectedSites([$this->italian => 'it-IT', $this->german => 'de-DE']);
    }

    public function testItSuggestsTheTranslationOfThePageOfThePolicyLinkOfAConnectedSite(): void
    {
        $cookiePolicy = $this->page($this->italian, 'Cookie policy');
        $this->updateOption($this->italian, ['url' => $this->permalink($this->italian, $cookiePolicy)]);
        $translation = $this->page($this->german, 'Cookie-Richtlinie');
        $this->sites->translate($this->italian, $cookiePolicy, $this->german, $translation);

        $this->assertSame($this->permalink($this->german, $translation), $this->sut()->for($this->german));
    }

    public function testItSuggestsTheTranslationOfThePrivacyPageWithoutAPolicyLink(): void
    {
        $privacy = $this->page($this->italian, 'Privacy');
        \update_blog_option($this->italian, 'wp_page_for_privacy_policy', $privacy);
        $translation = $this->page($this->german, 'Datenschutz');
        $this->sites->translate($this->italian, $privacy, $this->german, $translation);

        $this->assertSame($this->permalink($this->german, $translation), $this->sut()->for($this->german));
    }

    public function testThereIsNoSuggestionWithoutATranslation(): void
    {
        $cookiePolicy = $this->page($this->italian, 'Cookie policy');
        $this->updateOption($this->italian, ['url' => $this->permalink($this->italian, $cookiePolicy)]);

        $this->assertSame('', $this->sut()->for($this->german));
    }

    public function testThereIsNoSuggestionForADraftTranslation(): void
    {
        $cookiePolicy = $this->page($this->italian, 'Cookie policy');
        $this->updateOption($this->italian, ['url' => $this->permalink($this->italian, $cookiePolicy)]);
        $translation = $this->page($this->german, 'Cookie-Richtlinie', 'draft');
        $this->sites->translate($this->italian, $cookiePolicy, $this->german, $translation);

        $this->assertSame('', $this->sut()->for($this->german));
    }

    private function sut(): PolicySuggestion
    {
        return new PolicySuggestion($this->sites);
    }

    private function site(): int
    {
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);

        return $siteId;
    }

    private function page(int $siteId, string $title, string $status = 'publish'): int
    {
        \switch_to_blog($siteId);
        $pageId = self::factory()->post->create([
            'post_type' => 'page',
            'post_title' => $title,
            'post_status' => $status,
        ]);
        \restore_current_blog();
        $this->assertIsInt($pageId);

        return $pageId;
    }

    private function permalink(int $siteId, int $pageId): string
    {
        \switch_to_blog($siteId);
        $permalink = (string) \get_permalink($pageId);
        \restore_current_blog();

        return $permalink;
    }

    /**
     * @param array<string, mixed> $option
     */
    private function updateOption(int $siteId, array $option): void
    {
        \switch_to_blog($siteId);
        \update_option(Schema::OPTION, $option);
        \restore_current_blog();
    }
}
