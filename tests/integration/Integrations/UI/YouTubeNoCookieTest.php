<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\UI;

use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyCookieChoices\Integrations\UI\YouTubeNoCookie;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class YouTubeNoCookieTest extends WPTestCase
{
    use BuildsOptions;

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1', 'youtube_nocookie' => '1']);
    }

    public function testItChangesTheEmbedsBeforeTheBlocking(): void
    {
        $this->assertSame(
            ['embed_oembed_html' => 'replaceHost'],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function embeds(): iterable
    {
        yield 'www' => [
            '<iframe src="https://www.youtube.com/embed/abc?feature=oembed" allowfullscreen></iframe>',
            '<iframe src="https://www.youtube-nocookie.com/embed/abc?feature=oembed" allowfullscreen></iframe>',
        ];
        yield 'without www' => [
            '<iframe src="https://youtube.com/embed/abc"></iframe>',
            '<iframe src="https://www.youtube-nocookie.com/embed/abc"></iframe>',
        ];
        yield 'protocol relative' => [
            '<iframe src="//www.youtube.com/embed/abc"></iframe>',
            '<iframe src="//www.youtube-nocookie.com/embed/abc"></iframe>',
        ];
        yield 'not an embed' => [
            '<iframe src="https://www.youtube.com/watch?v=abc"></iframe>',
            '<iframe src="https://www.youtube.com/watch?v=abc"></iframe>',
        ];
        yield 'another host' => [
            '<iframe src="https://notyoutube.com/embed/abc"></iframe>',
            '<iframe src="https://notyoutube.com/embed/abc"></iframe>',
        ];
    }

    /**
     * @dataProvider embeds
     */
    public function testTheYouTubeEmbedsUseThePrivacyEnhancedHost(string $html, string $expected): void
    {
        $this->assertSame($expected, $this->makeSut()->replaceHost($html));
    }

    public function testTheEmbedsAreKeptWhenTheSettingIsOff(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);
        $html = '<iframe src="https://www.youtube.com/embed/abc"></iframe>';

        $this->assertSame($html, $this->makeSut()->replaceHost($html));
        $this->assertNull($this->makeSut()->replaceHost(null));
    }

    private function makeSut(): YouTubeNoCookie
    {
        return new YouTubeNoCookie(new IntegrationSettings($this->options()));
    }
}
