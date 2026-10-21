<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\CookieChoices;

use Italy_Cookie_Choices\Core\Cookie_Choices;
use ItalyStrap\Config\Config;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Overclokk\Cookie\Cookie;

final class RunTest extends WPTestCase
{
    private const COOKIE_NAME = 'displayCookieConsent';

    /** @var array<mixed> */
    private array $cookie = [];

    /** @var array<mixed> */
    private array $server = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->cookie = $_COOKIE;
        $this->server = $_SERVER;
    }

    public function tearDown(): void
    {
        $_COOKIE = $this->cookie;
        $_SERVER = $this->server;
        parent::tearDown();
    }

    public function testItRegistersNothingWhenConsentCookieIsSet(): void
    {
        $_COOKIE[self::COOKIE_NAME] = 'y';
        $sut = $this->makeCookieChoices(['block' => '1', 'widget_block' => '1', 'all_block' => '1']);

        $sut->run();

        $this->assertFalse(\has_action('wp_footer', [$sut, 'print_script_inline']));
        $this->assertFalse(\has_filter('the_content', [$sut, 'autoErase']));
        $this->assertFalse(\has_filter('widget_display_callback', [$sut, 'widgetErase']));
        $this->assertFalse(\has_action('wp_head', [$sut, 'bufferBodyStart']));
    }

    public function testItPrintsTheBannerInTheFooterWhenConsentIsMissing(): void
    {
        $sut = $this->makeCookieChoices([]);

        $sut->run();

        $this->assertSame(-99999, \has_action('wp_footer', [$sut, 'print_script_inline']));
        $this->assertFalse(\has_filter('the_content', [$sut, 'autoErase']));
        $this->assertFalse(\has_filter('widget_display_callback', [$sut, 'widgetErase']));
        $this->assertFalse(\has_action('wp_head', [$sut, 'bufferBodyStart']));
        $this->assertFalse(\has_action('template_redirect', [$sut, 'bufferHeadStart']));
    }

    public function testItBlocksEmbedsInTheContent(): void
    {
        $sut = $this->makeCookieChoices(['block' => '1']);

        $sut->run();

        $this->assertSame(11, \has_filter('the_content', [$sut, 'autoErase']));
    }

    public function testItBlocksEmbedsInTheWidgets(): void
    {
        $sut = $this->makeCookieChoices(['widget_block' => '1']);

        $sut->run();

        $this->assertSame(11, \has_filter('widget_display_callback', [$sut, 'widgetErase']));
    }

    public function testTheWidgetFilterReceivesTheWidgetArguments(): void
    {
        $sut = $this->makeCookieChoices(['widget_block' => '1']);
        $sut->run();

        /** @var array{text: string} $instance */
        $instance = \apply_filters(
            'widget_display_callback',
            ['text' => '<iframe src="https://example.com"></iframe>'],
            new \WP_Widget('test', 'Test'),
            []
        );

        $this->assertStringStartsWith('<div class="el">', $instance['text']);
    }

    public function testItBuffersTheBodyWhenAllBlockIsActive(): void
    {
        $sut = $this->makeCookieChoices(['all_block' => '1']);

        $sut->run();

        $this->assertSame(1000000, \has_action('wp_head', [$sut, 'bufferBodyStart']));
        $this->assertSame(-1000000, \has_action('wp_footer', [$sut, 'bufferBodyEnd']));
        $this->assertSame(-99999, \has_action('wp_footer', [$sut, 'print_script_inline']));
        $this->assertFalse(\has_action('template_redirect', [$sut, 'bufferHeadStart']));
    }

    /**
     * @return iterable<string, array{array<string, string|list<string>>}>
     */
    public function blockedScriptOptions(): iterable
    {
        yield 'custom script block' => [['custom_script_block' => '<script src="tracker.js"></script>']];
        yield 'blocked iframe' => [['block_iframe' => ['youtube']]];
        yield 'blocked script' => [['block_script' => ['analytics']]];
        yield 'blocked embed' => [['block_embed' => ['flash']]];
    }

    /**
     * @dataProvider blockedScriptOptions
     * @param array<string, string|list<string>> $options
     */
    public function testItBuffersHeadAndFooterWhenScriptsAreBlockedWithAllBlock(array $options): void
    {
        $sut = $this->makeCookieChoices(\array_merge(['all_block' => '1'], $options));

        $sut->run();

        $this->assertSame(2, \has_action('template_redirect', [$sut, 'bufferHeadStart']));
        $this->assertSame(99999, \has_action('wp_head', [$sut, 'bufferHeadEnd']));
        $this->assertSame(-99998, \has_action('wp_footer', [$sut, 'bufferFooterStart']));
        $this->assertSame(-1000000, \has_action('shutdown', [$sut, 'bufferFooterEnd']));
        $this->assertFalse(\has_action('wp_footer', [$sut, 'print_script_inline']));
    }

    /**
     * @dataProvider blockedScriptOptions
     * @param array<string, string|list<string>> $options
     */
    public function testItDoesNotBufferHeadAndFooterWithoutAllBlock(array $options): void
    {
        $sut = $this->makeCookieChoices($options);

        $sut->run();

        $this->assertFalse(\has_action('template_redirect', [$sut, 'bufferHeadStart']));
        $this->assertFalse(\has_action('wp_head', [$sut, 'bufferHeadEnd']));
        $this->assertSame(-99999, \has_action('wp_footer', [$sut, 'print_script_inline']));
    }

    public function testEmptyBlockedScriptsDoNotBufferHeadAndFooter(): void
    {
        $sut = $this->makeCookieChoices([
            'all_block' => '1',
            'block_iframe' => [''],
            'block_script' => [''],
            'block_embed' => [''],
        ]);

        $sut->run();

        $this->assertFalse(\has_action('template_redirect', [$sut, 'bufferHeadStart']));
        $this->assertSame(-99999, \has_action('wp_footer', [$sut, 'print_script_inline']));
    }

    public function testItBuildsThePlaceholderForBlockedContent(): void
    {
        $sut = $this->makeCookieChoices([
            'banner_bg' => '#123456',
            'banner_text_color' => '#abcdef',
            'content_message_text' => 'Content <strong>blocked</strong><script>alert(1)</script>',
            'content_message_button_text' => 'Allow "all"',
        ]);
        $sut->run();

        $this->assertSame(
            '<div class="el"><div style="padding:10px;margin-bottom: 18px;color:#abcdef;background-color:#123456;'
            . 'text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);">Content <strong>blocked</strong>alert(1)'
            . '&nbsp;&nbsp;<button onclick="cookieChoices.removeCookieConsent()" style="color:#abcdef;'
            . 'padding: 3px;font-size: 12px;line-height: 12px;text-decoration: none;text-transform: uppercase;'
            . 'margin:0;display: inline-block;font-weight: normal; text-align: center;  vertical-align: middle;'
            . '  cursor: pointer;  border: 1px solid #abcdef;background: rgba(255, 255, 255, 0.03);">'
            . 'Allow &quot;all&quot;</button></div><cookie></div>',
            $sut->autoErase('<iframe src="https://example.com"></iframe>')
        );
    }

    public function testThePlaceholderHasDefaultColorsAndEmptyTexts(): void
    {
        $sut = $this->makeCookieChoices([]);
        $sut->run();

        $placeholder = $sut->autoErase('<iframe src="https://example.com"></iframe>');
        $this->assertIsString($placeholder);

        $this->assertStringContainsString('color:#000000;background-color:#ffffff;', $placeholder);
        $this->assertStringContainsString(');">&nbsp;&nbsp;<button', $placeholder);
        $this->assertStringContainsString('0.03);"></button>', $placeholder);
    }

    public function testItAcceptsConsentOnTheSecondPageView(): void
    {
        $this->simulateSecondPageView();
        $sut = $this->makeCookieChoices(['secondView' => '1', 'url' => 'http://wordpress/privacy/']);

        $sut->run();

        $this->assertFalse(\has_action('wp_footer', [$sut, 'print_script_inline']));
    }

    /**
     * @return iterable<string, array{array<string, string>, array<string, string|null>}>
     */
    public function secondViewNotAccepted(): iterable
    {
        yield 'option disabled' => [[], []];
        yield 'not an html request' => [['secondView' => '1'], ['HTTP_ACCEPT' => 'application/json']];
        yield 'missing accept header' => [['secondView' => '1'], ['HTTP_ACCEPT' => null]];
        yield 'missing referer' => [['secondView' => '1'], ['HTTP_REFERER' => null]];
        yield 'page refresh' => [['secondView' => '1'], ['HTTP_REFERER' => 'http://wordpress/current-page/']];
        yield 'external referer' => [['secondView' => '1'], ['HTTP_REFERER' => 'http://example.com/previous-page/']];
        yield 'on the policy page' => [['secondView' => '1', 'url' => 'http://wordpress/current-page/'], []];
        yield 'on the policy page with port' => [
            ['secondView' => '1', 'url' => 'http://wordpress:8080/current-page/'],
            ['SERVER_PORT' => '8080'],
        ];
        yield 'on the policy page over https' => [
            ['secondView' => '1', 'url' => 'https://wordpress/current-page/'],
            ['HTTPS' => 'on'],
        ];
    }

    /**
     * @dataProvider secondViewNotAccepted
     * @param array<string, string> $options
     * @param array<string, string|null> $server
     */
    public function testItDoesNotAcceptConsentOnTheSecondPageView(array $options, array $server): void
    {
        $this->simulateSecondPageView();
        foreach ($server as $key => $value) {
            $this->setServer($key, $value);
        }
        $sut = $this->makeCookieChoices(\array_merge(['url' => 'http://wordpress/privacy/'], $options));

        $sut->run();

        $this->assertSame(-99999, \has_action('wp_footer', [$sut, 'print_script_inline']));
    }

    private function simulateSecondPageView(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml';
        $_SERVER['HTTP_HOST'] = 'wordpress';
        $_SERVER['SERVER_NAME'] = 'wordpress';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/current-page/';
        $_SERVER['HTTP_REFERER'] = 'http://wordpress/previous-page/';
        unset($_SERVER['HTTPS']);
    }

    /**
     * @param mixed $value
     */
    private function setServer(string $key, $value): void
    {
        if ($value === null) {
            unset($_SERVER[$key]);
            return;
        }

        $_SERVER[$key] = $value;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function makeCookieChoices(array $options): Cookie_Choices
    {
        return new Cookie_Choices(
            new Config(\array_merge(['cookie_name' => self::COOKIE_NAME, 'cookie_value' => 'y'], $options)),
            new Cookie()
        );
    }
}
