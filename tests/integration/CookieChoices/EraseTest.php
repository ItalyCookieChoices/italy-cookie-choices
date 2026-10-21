<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\CookieChoices;

use Italy_Cookie_Choices\Core\Cookie_Choices;
use ItalyStrap\Config\Config;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Overclokk\Cookie\Cookie;

final class EraseTest extends WPTestCase
{
    private const PLACEHOLDER_START = '<div class="el">';

    private const PLACEHOLDER_END = 'Allow</button></div><cookie></div>';

    public function testAutoEraseReplacesEveryEmbedWithThePlaceholder(): void
    {
        $sut = $this->makeCookieChoices();
        $iframe = '<iframe src="https://www.youtube.com/embed/x"></iframe>';
        $script = "<script type=\"text/javascript\">\nvar a = 1;\n</script>";
        $embed = '<embed src="movie.swf">';

        $content = $sut->autoErase('<p>Before</p>' . $iframe . '<p>Middle</p>' . $script . $embed . '<p>After</p>');
        $this->assertIsString($content);

        $this->assertSame(3, \substr_count($content, self::PLACEHOLDER_START));
        $this->assertSame(3, \substr_count($content, self::PLACEHOLDER_END));
        $this->assertStringStartsWith('<p>Before</p>' . self::PLACEHOLDER_START, $content);
        $this->assertStringContainsString(self::PLACEHOLDER_END . '<p>Middle</p>', $content);
        $this->assertStringEndsWith(self::PLACEHOLDER_END . '<p>After</p>', $content);
        $this->assertSame([$iframe, $script, $embed], $sut->js_array);
    }

    public function testAutoEraseMatchesTagsCaseInsensitively(): void
    {
        $sut = $this->makeCookieChoices();

        $content = $sut->autoErase('<IFRAME src="x"></IFRAME>');
        $this->assertIsString($content);

        $this->assertStringStartsWith(self::PLACEHOLDER_START, $content);
        $this->assertSame(['<IFRAME src="x"></IFRAME>'], $sut->js_array);
    }

    public function testAutoEraseKeepsContentWithoutEmbeds(): void
    {
        $sut = $this->makeCookieChoices();

        $this->assertSame('<p>Nothing to block</p>', $sut->autoErase('<p>Nothing to block</p>'));
        $this->assertSame([], $sut->js_array);
    }

    public function testAutoEraseAccumulatesEmbedsAcrossCalls(): void
    {
        $sut = $this->makeCookieChoices();

        $sut->autoErase('<iframe src="first"></iframe>');
        $sut->autoErase('<iframe src="second"></iframe>');

        $this->assertSame(['<iframe src="first"></iframe>', '<iframe src="second"></iframe>'], $sut->js_array);
    }

    public function testMatchesCollectsEmbedsWithTheInternalPattern(): void
    {
        $sut = $this->makeCookieChoices();

        $sut->matches('ignored', '<p>text</p><script>a()</script>');

        $this->assertSame(['<script>a()</script>'], $sut->js_array);
    }

    public function testMatchesKeepsPreviousEmbedsWhenNothingIsFound(): void
    {
        $sut = $this->makeCookieChoices();
        $sut->matches('ignored', '<script>a()</script>');

        $sut->matches('ignored', '<p>text</p>');

        $this->assertSame(['<script>a()</script>'], $sut->js_array);
    }

    public function testWidgetEraseReplacesEmbedsInNestedInstanceValues(): void
    {
        $sut = $this->makeCookieChoices();
        $instance = [
            'title' => 'Video',
            'text' => '<iframe src="https://vimeo.com/x"></iframe>',
            'nested' => ['html' => '<script>track()</script>', 'count' => 3],
            'empty' => '',
            'flag' => true,
        ];

        /**
         * @var array{
         *     title: string,
         *     text: string,
         *     nested: array{html: string, count: int},
         *     empty: string,
         *     flag: bool
         * } $result
         */
        $result = $sut->widgetErase($instance, new \WP_Widget('test', 'Test'), []);

        $this->assertSame('Video', $result['title']);
        $this->assertStringStartsWith(self::PLACEHOLDER_START, $result['text']);
        $this->assertStringStartsWith(self::PLACEHOLDER_START, $result['nested']['html']);
        $this->assertSame(3, $result['nested']['count']);
        $this->assertSame('', $result['empty']);
        $this->assertTrue($result['flag']);
        $this->assertSame(['<iframe src="https://vimeo.com/x"></iframe>', '<script>track()</script>'], $sut->js_array);
    }

    public function testWidgetEraseReturnsNonArrayInstanceUnchanged(): void
    {
        $sut = $this->makeCookieChoices();

        $this->assertFalse($sut->widgetErase(false, new \WP_Widget('test', 'Test'), []));
    }

    public function testWpJsonEncodeEncodesData(): void
    {
        $sut = $this->makeCookieChoices();

        $this->assertSame('["<script>","a\/b"]', $sut->wp_json_encode(['<script>', 'a/b']));
        $this->assertSame('{"a":1}', $sut->wp_json_encode(['a' => 1], JSON_FORCE_OBJECT));
    }

    public function testWpJsonEncodeReturnsFalseOnTooDeepData(): void
    {
        $sut = $this->makeCookieChoices();

        $this->assertFalse($sut->wp_json_encode([[1]], 0, 1));
    }

    private function makeCookieChoices(): Cookie_Choices
    {
        $sut = new Cookie_Choices(
            new Config([
                'cookie_name' => 'displayCookieConsent',
                'content_message_button_text' => 'Allow',
            ]),
            new Cookie()
        );
        $sut->run();

        return $sut;
    }
}
