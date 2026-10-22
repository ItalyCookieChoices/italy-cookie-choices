<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\CookieChoices;

use Italy_Cookie_Choices\Core\Cookie_Choices;
use ItalyStrap\Config\Config;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Overclokk\Cookie\Cookie;

/**
 * The 2.x banner moved to ItalyCookieChoices\Consent\UI\Banner in #235.
 * print_script_inline() prints only the JS variables and the 2.x library,
 * that is still needed to restore the blocked content.
 */
final class PrintScriptInlineTest extends WPTestCase
{
    private const EMPTY_BANNER_VARIABLES = 'scroll="",elPos="fixed",infoClass="",closeClass="",htmlM="",rel="",'
        . 'tar="",bgB="",btcB="",bPos="",bannerStyle="",contentStyle="",consText=""';

    public function testItPrintsNothingWhenNotActive(): void
    {
        $options = $this->options();
        unset($options['active']);

        $this->assertSame('', $this->render($options));
    }

    public function testItPrintsNothingOnFeeds(): void
    {
        $this->go_to(\get_feed_link());

        $this->assertSame('', $this->render($this->options()));
    }

    public function testItPrintsTheVariablesAndTheLibraryWithTheDefaults(): void
    {
        $this->assertSame($this->expectedOutput('displayCookieConsent', 'y', '[]'), $this->render($this->options()));
    }

    public function testCookieNameAndValueComeFromTheOptions(): void
    {
        $output = $this->render(['cookie_name' => 'myConsent', 'cookie_value' => 'accepted'] + $this->options());

        $this->assertSame($this->expectedOutput('myConsent', 'accepted', '[]'), $output);
    }

    public function testCookieNameAndValueHaveDefaults(): void
    {
        $options = $this->options();
        unset($options['cookie_name'], $options['cookie_value']);

        $this->assertStringContainsString('var coNA="displayCookieConsent",coVA="y";', $this->render($options));
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public function legacyBannerOptions(): iterable
    {
        yield 'top bar with html margin' => [['banner' => '1', 'html_margin' => '1']];
        yield 'dialog' => [['banner' => '2']];
        yield 'bottom bar' => [['banner' => '3']];
        yield 'big button template' => [['js_template' => 'bigbutton']];
        yield 'custom template' => [['js_template' => 'custom', 'customCSS' => '#cookieChoiceInfo{top:0}']];
        yield 'behaviour flags' => [['scroll' => '1', 'reload' => '1', 'target' => '1']];
        yield 'colors and classes' => [[
            'banner_bg' => '#111111',
            'banner_text_color' => '#222222',
            'bannerStyle' => 'my-banner',
            'contentStyle' => 'my-content',
            'consentText' => 'my-text',
            'infoClass' => 'my-info',
            'closeClass' => 'my-close',
        ]];
        yield 'texts' => [[
            'text' => 'This site uses cookies',
            'url' => 'http://wordpress/privacy/',
            'anchor_text' => 'Read more',
            'button_text' => 'Accept',
        ]];
    }

    /**
     * @dataProvider legacyBannerOptions
     * @param array<string, string> $bannerOptions
     */
    public function testTheLegacyBannerOptionsDoNotChangeTheOutput(array $bannerOptions): void
    {
        $output = $this->render($bannerOptions + $this->options());

        $this->assertSame($this->expectedOutput('displayCookieConsent', 'y', '[]'), $output);
    }

    public function testItPrintsNoBannerStyleOrNoscript(): void
    {
        $output = $this->render([
            'banner' => '2',
            'text' => 'This site uses cookies',
            'url' => 'http://wordpress/privacy/',
            'anchor_text' => 'Read more',
            'button_text' => 'Accept',
        ] + $this->options());

        $this->assertStringNotContainsString('<style', $output);
        $this->assertStringNotContainsString('<noscript', $output);
        $this->assertStringNotContainsString('cookieChoices.showCookieConsent', $output);
        $this->assertStringNotContainsString('DOMContentLoaded', $output);
        $this->assertStringNotContainsString('This site uses cookies', $output);
    }

    public function testWpJsonEncodeDefaultDepthIs512(): void
    {
        $data = 1;
        for ($level = 0; $level < 512; $level++) {
            $data = [$data];
        }
        $sut = $this->makeCookieChoices([]);

        $this->assertIsString($sut->wp_json_encode($data));
        $this->assertFalse($sut->wp_json_encode([$data]));
    }

    public function testTheJavascriptArrayCanBeFiltered(): void
    {
        \add_filter('icc_js_array', static function (array $scripts): array {
            $scripts[] = '<script src="a.js"></script>';
            return $scripts;
        });

        $this->assertSame(
            $this->expectedOutput('displayCookieConsent', 'y', '["<script src=\"a.js\"><\/script>"]'),
            $this->render($this->options())
        );
    }

    public function testTheBlockedScriptsArePassedToTheJavascriptArray(): void
    {
        $sut = $this->makeCookieChoices($this->options());
        $sut->autoErase('<iframe src="https://example.com"></iframe>');

        $this->assertStringContainsString(
            'jsArr = ["<iframe src=\"https:\/\/example.com\"><\/iframe>"];',
            $this->capture($sut)
        );
    }

    public function testTheOutputCanBeFiltered(): void
    {
        \add_filter('icc_output_html', static fn(string $html): string => 'filtered:' . $html);

        $this->assertSame(
            'filtered:' . $this->expectedOutput('displayCookieConsent', 'y', '[]'),
            $this->render($this->options())
        );
    }

    private function expectedOutput(string $cookieName, string $cookieValue, string $jsArray): string
    {
        return '<!-- Italy Cookie Choices --><script>'
            . 'var coNA="' . $cookieName . '",coVA="' . $cookieValue . '";'
            . self::EMPTY_BANNER_VARIABLES . ',jsArr = ' . $jsArray . ';'
            . $this->cookieChoicesScript()
            . '</script>';
    }

    private function cookieChoicesScript(): string
    {
        $file = WP_DEBUG ? '/js/default/cookiechoices.js' : '/js/default/cookiechoices.php';

        return (string) \file_get_contents(ITALY_COOKIE_CHOICES_DIRNAME . $file);
    }

    /**
     * @return array<string, string>
     */
    private function options(): array
    {
        return [
            'active' => '1',
            'cookie_name' => 'displayCookieConsent',
            'cookie_value' => 'y',
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function render(array $options): string
    {
        return $this->capture($this->makeCookieChoices($options));
    }

    private function capture(Cookie_Choices $sut): string
    {
        \ob_start();
        $sut->print_script_inline();

        return (string) \ob_get_clean();
    }

    /**
     * @param array<string, mixed> $options
     */
    private function makeCookieChoices(array $options): Cookie_Choices
    {
        return new Cookie_Choices(new Config($options), new Cookie());
    }
}
