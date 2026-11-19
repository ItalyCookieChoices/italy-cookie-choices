<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Multilingual;

use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Multilingual\Application\LinkTranslations;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\Multilingual\FakeLanguageTranslations;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class LinkTranslationsTest extends WPTestCase
{
    private FakeLanguageTranslations $translations;
    private int $policy = 0;

    public function setUp(): void
    {
        parent::setUp();
        $this->translations = new FakeLanguageTranslations(['it' => 'Italiano', 'de' => 'Deutsch', 'es' => 'Español']);
        $this->policy = $this->page('Cookie policy');
        \update_option(Schema::OPTION, ['url' => (string) \get_permalink($this->policy)]);
    }

    public function testItProposesTheTranslationOfThePolicyPageForEachOtherLanguage(): void
    {
        $german = $this->page('Cookie-Richtlinie');
        $this->translations->translatePost($this->policy, 'de', $german);
        $this->translations->strings['Banner url:es'] = 'https://example.org/politica/';

        $this->assertSame(
            [
                [
                    'language' => 'de',
                    'name' => 'Deutsch',
                    'current' => '',
                    'proposal' => (string) \get_permalink($german),
                ],
                [
                    'language' => 'es',
                    'name' => 'Español',
                    'current' => 'https://example.org/politica/',
                    'proposal' => '',
                ],
            ],
            $this->sut()->rows(BannerLink::Policy)
        );
    }

    public function testThePrivacyPageIsUsedWithoutAPolicyLink(): void
    {
        \update_option(Schema::OPTION, ['url' => '']);
        \update_option('wp_page_for_privacy_policy', $this->policy);
        $german = $this->page('Datenschutz');
        $this->translations->translatePost($this->policy, 'de', $german);

        $this->assertSame(
            (string) \get_permalink($german),
            $this->sut()->rows(BannerLink::Policy)[0]['proposal'] ?? null
        );
    }

    public function testADraftTranslationIsNotProposed(): void
    {
        $this->translations->translatePost($this->policy, 'de', $this->page('Entwurf', 'draft'));

        $this->assertSame('', $this->sut()->rows(BannerLink::Policy)[0]['proposal'] ?? null);
    }

    public function testTheAdminUsesTheProposal(): void
    {
        $german = $this->page('Cookie-Richtlinie');
        $this->translations->translatePost($this->policy, 'de', $german);

        $rows = $this->sut()->use(BannerLink::Policy, 'de');

        $this->assertSame((string) \get_permalink($german), $this->translations->strings['Banner url:de'] ?? null);
        $this->assertSame((string) \get_permalink($german), $rows[0]['current'] ?? null);
    }

    public function testALanguageWithoutAProposalCannotUseIt(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->sut()->use(BannerLink::Policy, 'es');
    }

    public function testWithoutAMultilingualPluginThereAreNoRows(): void
    {
        $this->assertSame([], (new LinkTranslations(new FakeLanguageTranslations()))->rows(BannerLink::Policy));
    }

    public function testItProposesTheTranslationOfTheLegalNoticePage(): void
    {
        $notice = $this->page('Legal notice');
        $german = $this->page('Impressum');
        $this->translations->translatePost($notice, 'de', $german);
        \update_option(Schema::OPTION, ['legal_notice_url' => (string) \get_permalink($notice)]);

        $rows = $this->sut()->use(BannerLink::LegalNotice, 'de');

        $this->assertSame(
            (string) \get_permalink($german),
            $this->translations->strings['Banner legal notice url:de'] ?? null
        );
        $this->assertSame((string) \get_permalink($german), $rows[0]['current'] ?? null);
    }

    public function testTheLegalNoticeHasNoPageWithoutItsLink(): void
    {
        \update_option('wp_page_for_privacy_policy', $this->policy);
        $this->translations->translatePost($this->policy, 'de', $this->page('Datenschutz'));

        $this->assertSame('', $this->sut()->rows(BannerLink::LegalNotice)[0]['proposal'] ?? null);
    }

    private function sut(): LinkTranslations
    {
        return new LinkTranslations($this->translations);
    }

    private function page(string $title, string $status = 'publish'): int
    {
        $page = self::factory()->post->create([
            'post_type' => 'page',
            'post_title' => $title,
            'post_status' => $status,
        ]);
        $this->assertIsInt($page);

        return $page;
    }
}
