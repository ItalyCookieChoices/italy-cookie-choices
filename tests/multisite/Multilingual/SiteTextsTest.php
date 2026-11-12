<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Multilingual;

use ItalyCookieChoices\Multilingual\Application\SiteTexts;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\Multilingual\FakeConnectedSites;
use ItalyCookieChoices\Tests\Support\Multilingual\FakeTranslator;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SiteTextsTest extends WPTestCase
{
    private const ITALIAN = [
        'banner' => '2',
        'text' => 'Usiamo i cookie',
        'url' => 'https://example.com/it/policy/',
        'anchor_text' => 'Cookie policy',
        'button_text' => 'Accetta',
        'categories' => ['statistics' => ['label' => 'Statistiche', 'description' => 'Contano le visite']],
    ];

    private int $italian = 0;
    private int $german = 0;

    public function setUp(): void
    {
        parent::setUp();
        $this->italian = $this->site(self::ITALIAN);
        $this->german = $this->site(['banner' => '3', 'button_text' => 'Akzeptieren']);
    }

    public function testItReadsTheTextsOfASite(): void
    {
        $this->assertSame(
            [
                'text' => 'Usiamo i cookie',
                'url' => 'https://example.com/it/policy/',
                'anchor_text' => 'Cookie policy',
                'button_text' => 'Accetta',
                'content_message_text' => '',
                'content_message_button_text' => '',
                'floating_button_label' => '',
                'categories' => ['statistics' => ['label' => 'Statistiche', 'description' => 'Contano le visite']],
            ],
            $this->sut()->of($this->italian)
        );
    }

    public function testItSavesOnlyTheTextsThroughTheSanitizer(): void
    {
        $texts = $this->sut()->save($this->german, [
            'text' => 'Wir verwenden Cookies<script>x</script>',
            'banner' => '5',
        ]);

        $this->assertSame('Wir verwenden Cookiesx', $texts['text']);
        $option = \get_blog_option($this->german, Schema::OPTION);
        $this->assertIsArray($option);
        $this->assertSame('3', $option['banner'] ?? null);
        $this->assertSame('Akzeptieren', $option['button_text'] ?? null);
    }

    public function testASiteHasItsOwnTextsWhenOneIsWritten(): void
    {
        $empty = $this->site(['banner' => '1']);

        $this->assertTrue($this->sut()->hasOwnTexts($this->italian));
        $this->assertTrue($this->sut()->hasOwnTexts($this->german));
        $this->assertFalse($this->sut()->hasOwnTexts($empty));
    }

    public function testTheDraftCopiesOnlyTheEmptyTextsAndNotThePolicyLink(): void
    {
        $draft = $this->sut()->draft($this->german, $this->italian, false);

        $this->assertSame('Usiamo i cookie', $draft['text']);
        $this->assertSame('Cookie policy', $draft['anchor_text']);
        $this->assertSame('Akzeptieren', $draft['button_text']);
        $this->assertSame('', $draft['url']);
        $this->assertSame(
            ['statistics' => ['label' => 'Statistiche', 'description' => 'Contano le visite']],
            $draft['categories']
        );
        // The draft is not saved.
        $option = \get_blog_option($this->german, Schema::OPTION);
        $this->assertIsArray($option);
        $this->assertArrayNotHasKey('text', $option);
    }

    public function testTheDraftTranslatesTheTextsInTheLanguageOfTheSite(): void
    {
        $translator = new FakeTranslator();

        $draft = $this->sut($translator)->draft($this->german, $this->italian, true);

        $this->assertSame('[de-DE] Usiamo i cookie', $draft['text']);
        $this->assertSame('[de-DE] Cookie policy', $draft['anchor_text']);
        $this->assertSame('Akzeptieren', $draft['button_text']);
        $this->assertSame(
            ['statistics' => ['label' => '[de-DE] Statistiche', 'description' => '[de-DE] Contano le visite']],
            $draft['categories']
        );
        $this->assertNotContains('Accetta', $translator->translated);
    }

    public function testTheDraftIsNotTranslatedWithoutATranslationService(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->sut(new FakeTranslator(false))->draft($this->german, $this->italian, true);
    }

    public function testTheDraftComesOnlyFromAConnectedSite(): void
    {
        $other = $this->site(self::ITALIAN);

        $this->expectException(\InvalidArgumentException::class);

        $this->sut()->draft($this->german, $other, false);
    }

    private function sut(?FakeTranslator $translator = null): SiteTexts
    {
        return new SiteTexts(
            new FakeConnectedSites([$this->italian => 'it-IT', $this->german => 'de-DE']),
            $translator ?? new FakeTranslator()
        );
    }

    /**
     * @param array<string, mixed> $option
     */
    private function site(array $option): int
    {
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);
        \switch_to_blog($siteId);
        \update_option(Schema::OPTION, $option);
        \restore_current_blog();

        return $siteId;
    }
}
