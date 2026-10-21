<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\CookieChoices;

use Italy_Cookie_Choices\Core\Cookie_Choices;
use ItalyStrap\Config\Config;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Overclokk\Cookie\Cookie;

final class BufferTest extends WPTestCase
{
    private const SEP = '<---------SEP--------->';

    private const SOMETHING = '<---------SOMETHING--------->';

    private const REMOVED = '<!-- removed head from Italy Cookie Choices PHP Class -->';

    private const PLACEHOLDER = '<div class="el"><div style="padding:10px;margin-bottom: 18px;color:#000000;'
        . 'background-color:#ffffff;text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);">&nbsp;&nbsp;'
        . '<button onclick="cookieChoices.removeCookieConsent()" style="color:#000000;padding: 3px;'
        . 'font-size: 12px;line-height: 12px;text-decoration: none;text-transform: uppercase;margin:0;'
        . 'display: inline-block;font-weight: normal; text-align: center;  vertical-align: middle;  cursor: pointer;'
        . '  border: 1px solid #000000;background: rgba(255, 255, 255, 0.03);"></button></div><cookie></div>';

    private array $server = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
    }

    public function tearDown(): void
    {
        $_SERVER = $this->server;
        parent::tearDown();
    }

    public function testRemoveCustomScriptKeepsTheBufferWithoutScriptsToBlock(): void
    {
        $sut = $this->makeCookieChoices([]);
        $buffer = '<head><script src="https://example.com/tracker.js"></script></head>';

        $this->assertSame($buffer, $sut->removeCustomScript($buffer));
        $this->assertSame([], $sut->js_array);
    }

    public function testRemoveCustomScriptRemovesEveryCustomScript(): void
    {
        $first = '<script src="https://example.com/first.js"></script>';
        $second = '<script>second();</script>';
        $sut = $this->makeCookieChoices(['custom_script_block' => $first . "\r\n" . self::SEP . "\n" . $second]);

        $result = $sut->removeCustomScript('<head>' . $first . '<meta charset="utf-8">' . $second . '</head>');

        $this->assertSame('<head>' . self::REMOVED . '<meta charset="utf-8">' . self::REMOVED . '</head>', $result);
        $this->assertSame([$first, $second], $sut->js_array);
    }

    public function testRemoveCustomScriptSupportsTheSomethingWildcard(): void
    {
        $sut = $this->makeCookieChoices([
            'custom_script_block' => '<script' . self::SOMETHING . 'tracker.js' . self::SOMETHING . '</script>',
        ]);

        $result = $sut->removeCustomScript(
            '<script async src="https://cdn.example.com/tracker.js?v=2"></script><p>ok</p>'
        );

        $this->assertSame(self::REMOVED . '<p>ok</p>', $result);
        $this->assertSame(['<script async src="https://cdn.example.com/tracker.js?v=2"></script>'], $sut->js_array);
    }

    public function testRemoveCustomScriptRemovesEveryOccurrence(): void
    {
        $script = '<script>track();</script>';
        $sut = $this->makeCookieChoices(['custom_script_block' => $script]);

        $result = $sut->removeCustomScript($script . '<p>ok</p>' . $script);

        $this->assertSame(self::REMOVED . '<p>ok</p>' . self::REMOVED, $result);
        $this->assertSame([$script, $script], $sut->js_array);
    }

    public function blockedVendors(): iterable
    {
        yield 'iframe' => [
            ['block_iframe' => ['youtube', '']],
            '<iframe width="560" src="https://www.youtube.com/embed/x"></iframe>',
        ];
        yield 'script' => [
            ['block_script' => ['analytics']],
            '<script src="https://www.google-analytics.com/analytics.js"></script>',
        ];
        yield 'embed' => [
            ['block_embed' => ['flash']],
            '<embed src="https://example.com/flash.swf">',
        ];
    }

    /**
     * @dataProvider blockedVendors
     */
    public function testRemoveCustomScriptRemovesBlockedVendors(array $options, string $blocked): void
    {
        $sut = $this->makeCookieChoices($options);
        $sut->run();

        $result = $sut->removeCustomScript('<p>before</p>' . $blocked);

        $this->assertSame('<p>before</p>' . self::REMOVED, $result);
        $this->assertContains($blocked, $sut->js_array);
    }

    /**
     * @dataProvider blockedVendors
     */
    public function testRemoveCustomScriptKeepsOtherVendors(array $options): void
    {
        $sut = $this->makeCookieChoices($options);
        $sut->run();
        $buffer = '<iframe src="https://vimeo.com/x"></iframe><script src="https://example.com/a.js"></script>'
            . '<embed src="https://example.com/b.swf">';

        $this->assertSame($buffer, $sut->removeCustomScript($buffer));
    }

    public function testRemoveCustomScriptTrimsTheCustomScripts(): void
    {
        $script = '<script>track();</script>';
        $sut = $this->makeCookieChoices(['custom_script_block' => '  ' . $script . '  ']);

        $this->assertSame('<p>a</p>' . self::REMOVED, $sut->removeCustomScript('<p>a</p>' . $script));
    }

    /**
     * Current behaviour: the vendor pattern is greedy, it swallows everything
     * up to the last closing tag of the same kind (up to the last ">" for embeds).
     */
    public function testRemoveCustomScriptVendorPatternIsGreedy(): void
    {
        $sut = $this->makeCookieChoices(['block_iframe' => ['youtube']]);
        $sut->run();

        $result = $sut->removeCustomScript(
            '<iframe src="https://www.youtube.com/embed/x"></iframe><p>kept?</p>'
            . '<iframe src="https://vimeo.com/x"></iframe>'
        );

        $this->assertSame(self::REMOVED, $result);
    }

    public function testBufferBodyEndReplacesEmbedsAfterTheBodyTag(): void
    {
        $sut = $this->makeCookieChoices([]);
        $sut->run();
        $head = '<html><head><script src="head.js"></script></head>';
        $iframe = '<iframe src="https://example.com"></iframe>';

        $output = $this->captureBody($sut, $head . '<body class="home"><p>Hi</p>' . $iframe . '</body>');

        $this->assertSame(
            '<!-- ICCStartBody -->' . $head . '<body class="home"><p>Hi</p>' . self::PLACEHOLDER
            . '</body><!-- ICCEndBody -->',
            $output
        );
        $this->assertSame([$iframe], $sut->js_array);
    }

    public function testBufferBodyEndKeepsScriptsExcludedByTheCustomList(): void
    {
        $allowed = '<script src="https://example.com/allowed.js"></script>';
        $blocked = '<script src="https://example.com/blocked.js"></script>';
        $sut = $this->makeCookieChoices([
            'custom_script_block_body_exclude' => "<script\n" . self::SOMETHING . 'allowed.js' . self::SOMETHING
                . "</script>\r\n" . self::SEP . "\n<iframe" . self::SOMETHING . '</iframe>',
        ]);
        $sut->run();

        $output = $this->captureBody($sut, '<body>' . $allowed . $blocked . '</body>');

        $this->assertSame(
            '<!-- ICCStartBody --><body>' . $allowed . self::PLACEHOLDER . '</body><!-- ICCEndBody -->',
            $output
        );
        $this->assertSame([$blocked], $sut->js_array);
    }

    public function testBufferBodyEndKeepsAllowedVendors(): void
    {
        $iframe = '<iframe src="https://www.youtube.com/embed/x"></iframe>';
        $script = '<script src="https://platform.twitter.com/widgets.js"></script>';
        $embed = '<embed src="https://example.com/movie.swf">';
        $blockedIframe = '<iframe src="https://vimeo.com/x"></iframe>';
        $blockedScript = '<script src="https://example.com/tracker.js"></script>';
        $blockedEmbed = '<embed src="https://example.com/flash.swf">';
        $sut = $this->makeCookieChoices([
            'allow_iframe' => ['youtube', ''],
            'allow_script' => ['twitter'],
            'allow_embed' => ['movie'],
        ]);
        $sut->run();

        $output = $this->captureBody(
            $sut,
            '<body>' . $iframe . $script . $embed . $blockedIframe . $blockedScript . $blockedEmbed . '</body>'
        );

        $this->assertSame(
            '<!-- ICCStartBody --><body>' . $iframe . $script . $embed
            . self::PLACEHOLDER . self::PLACEHOLDER . self::PLACEHOLDER . '</body><!-- ICCEndBody -->',
            $output
        );
        $this->assertSame([$blockedIframe, $blockedScript, $blockedEmbed], $sut->js_array);
    }

    public function testBufferBodyEndKeepsASingleCustomExclusion(): void
    {
        $allowed = '<script src="https://example.com/allowed.js"></script>';
        $sut = $this->makeCookieChoices(['custom_script_block_body_exclude' => '  ' . $allowed . "  \n"]);
        $sut->run();

        $output = $this->captureBody($sut, '<body>' . $allowed . '</body>');

        $this->assertSame('<!-- ICCStartBody --><body>' . $allowed . '</body><!-- ICCEndBody -->', $output);
        $this->assertSame([], $sut->js_array);
    }

    public function testBufferBodyEndHandlesMultilineDocuments(): void
    {
        $sut = $this->makeCookieChoices([]);
        $sut->run();
        $head = "<html>\n<head>\n<title>Page</title>\n</head>\n";

        $output = $this->captureBody(
            $sut,
            $head . "<body class=\"home\">\n<p>Hi</p>\n<iframe\nsrc=\"https://example.com\"></iframe>\n</body>\n</html>"
        );

        $this->assertSame(
            '<!-- ICCStartBody -->' . $head . "<body class=\"home\">\n<p>Hi</p>\n" . self::PLACEHOLDER
            . "\n</body>\n</html><!-- ICCEndBody -->",
            $output
        );
    }

    public function testBufferBodyEndWithoutBodyTagOutputsOnlyTheMarkers(): void
    {
        $sut = $this->makeCookieChoices([]);
        $sut->run();

        $this->assertSame('<!-- ICCStartBody --><!-- ICCEndBody -->', $this->captureBody($sut, '<p>no body</p>'));
    }

    public function bufferStarts(): iterable
    {
        yield 'body' => ['bufferBodyStart'];
        yield 'head' => ['bufferHeadStart'];
        yield 'footer' => ['bufferFooterStart'];
    }

    /**
     * @dataProvider bufferStarts
     */
    public function testBufferStartFlushesThePreviousBufferAndOpensANewOne(string $method): void
    {
        $sut = $this->makeCookieChoices([]);
        $level = \ob_get_level();

        \ob_start();
        \ob_start();
        echo 'previous';
        $sut->{$method}();
        echo 'buffered';
        $buffered = \ob_get_clean();
        $previous = \ob_get_clean();
        $finalLevel = \ob_get_level();
        while (\ob_get_level() > $level) {
            \ob_end_clean();
        }

        $this->assertSame('buffered', $buffered);
        $this->assertSame('previous', $previous);
        $this->assertSame($level, $finalLevel);
    }

    /**
     * @dataProvider bufferStarts
     */
    public function testBufferStartOpensANewBufferWhenThePreviousIsEmpty(string $method): void
    {
        $sut = $this->makeCookieChoices([]);
        $level = \ob_get_level();

        \ob_start();
        $sut->{$method}();
        $openedLevel = \ob_get_level();
        while (\ob_get_level() > $level) {
            \ob_end_clean();
        }

        $this->assertSame($level + 2, $openedLevel);
    }

    public function testBufferHeadEndRemovesCustomScripts(): void
    {
        $script = '<script src="https://example.com/tracker.js"></script>';
        $sut = $this->makeCookieChoices(['custom_script_block' => $script]);

        \ob_start();
        $sut->bufferHeadStart();
        echo '<head>' . $script . '</head>';
        $sut->bufferHeadEnd();
        $output = \ob_get_clean();

        $this->assertSame('<!-- ICCStartHead --><head>' . self::REMOVED . '</head><!-- ICCEndHead -->', $output);
    }

    public function testBufferFooterEndRemovesScriptsAndPrintsTheBannerForHtmlRequests(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $script = '<script src="https://example.com/tracker.js"></script>';
        $sut = $this->makeCookieChoices(['custom_script_block' => $script] + $this->bannerOptions());

        \ob_start();
        $sut->bufferFooterStart();
        echo '<footer>' . $script . '</footer>';
        $sut->bufferFooterEnd();
        $output = \ob_get_clean();

        $this->assertStringStartsWith('<!-- Italy Cookie Choices -->', $output);
        $this->assertStringContainsString(
            'jsArr = ["<script src=\"https:\/\/example.com\/tracker.js\"><\/script>"];',
            $output
        );
        $this->assertStringEndsWith(
            '<!-- ICCStartFooter --><footer>' . self::REMOVED . '</footer><!-- ICCEndFooter -->',
            $output
        );
    }

    public function notHtmlAcceptHeaders(): iterable
    {
        yield 'json request' => ['application/json'];
        yield 'missing header' => [null];
    }

    /**
     * @dataProvider notHtmlAcceptHeaders
     */
    public function testBufferFooterEndOutputsTheBufferUntouchedForOtherRequests(?string $accept): void
    {
        unset($_SERVER['HTTP_ACCEPT']);
        if ($accept !== null) {
            $_SERVER['HTTP_ACCEPT'] = $accept;
        }
        $script = '<script src="https://example.com/tracker.js"></script>';
        $sut = $this->makeCookieChoices(['custom_script_block' => $script] + $this->bannerOptions());

        \ob_start();
        $sut->bufferFooterStart();
        echo '<footer>' . $script . '</footer>';
        $sut->bufferFooterEnd();
        $output = \ob_get_clean();

        $this->assertSame('<footer>' . $script . '</footer>', $output);
    }

    public function testFooterBuffersDoNothingOnFeeds(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $this->go_to(\get_feed_link());
        $sut = $this->makeCookieChoices(['custom_script_block' => '<script>a()</script>'] + $this->bannerOptions());
        $level = \ob_get_level();

        \ob_start();
        $sut->bufferFooterStart();
        $this->assertSame($level + 1, \ob_get_level());
        echo '<script>a()</script>';
        $sut->bufferFooterEnd();
        $output = \ob_get_clean();

        $this->assertSame('<script>a()</script>', $output);
    }

    private function captureBody(Cookie_Choices $sut, string $html): string
    {
        \ob_start();
        $sut->bufferBodyStart();
        echo $html;
        $sut->bufferBodyEnd();

        return (string) \ob_get_clean();
    }

    private function bannerOptions(): array
    {
        return [
            'active' => '1',
            'banner' => '1',
            'text' => 'Cookie text',
            'url' => 'http://wordpress/privacy/',
            'anchor_text' => 'More',
            'button_text' => 'Accept',
        ];
    }

    private function makeCookieChoices(array $options): Cookie_Choices
    {
        return new Cookie_Choices(
            new Config(\array_merge(['cookie_name' => 'displayCookieConsent'], $options)),
            new Cookie()
        );
    }
}
