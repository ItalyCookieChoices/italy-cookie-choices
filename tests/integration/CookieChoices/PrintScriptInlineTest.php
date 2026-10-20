<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\CookieChoices;

use Italy_Cookie_Choices\Core\Cookie_Choices;
use ItalyStrap\Config\Config;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Overclokk\Cookie\Cookie;

final class PrintScriptInlineTest extends WPTestCase
{
    private const DEFAULT_BUTTON_STYLE = '.italybtn{margin-left:10px;}';

    private array $cookie = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->cookie = $_COOKIE;
    }

    public function tearDown(): void
    {
        $_COOKIE = $this->cookie;
        parent::tearDown();
    }

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

    public function testItPrintsTheDefaultTopBar(): void
    {
        $output = $this->render($this->options());

        $this->assertSame(
            '<!-- Italy Cookie Choices --><style type="text/css">'
            . '#cookieChoiceInfo{background-color: #fff;color: #000;left:0;margin:0;padding:4px;position:fixed;'
            . 'text-align:center;top:0;width:100%;z-index:9999;}' . self::DEFAULT_BUTTON_STYLE
            . '</style><script>'
            . 'var coNA="displayCookieConsent",coVA="y";scroll="",elPos="fixed",infoClass="italybtn",'
            . 'closeClass="italybtn",htmlM="",rel="",tar="",bgB="#fff",btcB="#000",bPos="top:0",'
            . 'bannerStyle="bannerStyle",contentStyle="contentStyle",consText="consentText",jsArr = [];'
            . $this->cookieChoicesScript()
            . 'document.addEventListener("DOMContentLoaded", function(event) {cookieChoices.showCookieConsentBar('
            . '\'This site uses cookies\', "Accept", "Read more", "http://wordpress/privacy/");});'
            . '</script>'
            . '<noscript><style type="text/css">html{margin-top:35px}</style><div id="cookieChoiceInfo">'
            . '<span>This site uses cookies</span><a href="http://wordpress/privacy/" class="italybtn" '
            . 'target="_blank">Read more</a></div></noscript>',
            $output
        );
    }

    public function testItPrintsTheDialog(): void
    {
        $output = $this->render(['banner' => '2'] + $this->options());

        $this->assertStringContainsString(
            '<style type="text/css">.glassStyle{position:fixed;width:100%;height:100%;z-index:999;top:0;left:0;'
            . 'opacity:0.5;filter:alpha(opacity=50);background-color:#ccc;}.bannerStyle{min-width:100%;'
            . 'z-index:9999;position:fixed;top:25%;}.contentStyle{position:relative;background-color:#fff;'
            . 'padding:20px;box-shadow:4px 4px 25px #888;max-width:80%;margin:0 auto;}'
            . self::DEFAULT_BUTTON_STYLE . '</style>',
            $output
        );
        $this->assertStringContainsString('bPos="top:0"', $output);
        $this->assertStringContainsString('cookieChoices.showCookieConsentDialog(', $output);
    }

    public function testItPrintsTheBottomBar(): void
    {
        $output = $this->render(['banner' => '3'] + $this->options());

        $this->assertStringContainsString(
            '<style type="text/css">#cookieChoiceInfo{background-color: #fff;color: #000;left:0;margin:0;'
            . 'padding:4px;position:fixed;text-align:center;bottom:0;width:100%;z-index:9999;}'
            . self::DEFAULT_BUTTON_STYLE . '</style>',
            $output
        );
        $this->assertStringContainsString('bPos="bottom:0"', $output);
        $this->assertStringContainsString('cookieChoices.showCookieConsentBar(', $output);
    }

    public function unknownBanners(): iterable
    {
        yield 'unknown value' => ['4'];
        yield 'integer value' => [1];
    }

    /**
     * @dataProvider unknownBanners
     * @param mixed $banner
     */
    public function testUnknownBannersFallBackToTheTopBar($banner): void
    {
        $output = $this->render(['banner' => $banner, 'html_margin' => '1'] + $this->options());

        $this->assertStringContainsString(
            '<style type="text/css">#cookieChoiceInfo{background-color: #fff;color: #000;left:0;margin:0;'
            . 'padding:4px;position:fixed;text-align:center;top:0;width:100%;z-index:9999;}'
            . self::DEFAULT_BUTTON_STYLE . '</style>',
            $output
        );
        $this->assertStringContainsString('htmlM=""', $output);
        $this->assertStringContainsString('bPos="top:0"', $output);
        $this->assertStringContainsString('cookieChoices.showCookieConsentBar(', $output);
    }

    public function testTheTopBarAddsTheHtmlMargin(): void
    {
        $this->assertStringContainsString(
            'htmlM="1"',
            $this->render(['html_margin' => '1'] + $this->options())
        );
    }

    public function testTheBottomBarIgnoresTheHtmlMargin(): void
    {
        $this->assertStringContainsString(
            'htmlM=""',
            $this->render(['banner' => '3', 'html_margin' => '1'] + $this->options())
        );
    }

    public function templates(): iterable
    {
        $bigButton = '.italybtn{color:#000;padding:7px 12px;font-size:18px;line-height:18px;text-decoration:none;'
            . 'text-transform:uppercase;margin:10px 20px 2px 0;letter-spacing: 0.125em;display:inline-block;'
            . 'font-weight:normal;text-align:center;  vertical-align:middle;cursor:pointer;border:1px solid #000;'
            . 'background:rgba(255, 255, 255, 0.03);}.consentText{display:block}';
        $smallButton = '.italybtn{color:#000;padding:3px 7px;font-size:14px;line-height:14px;text-decoration:none;'
            . 'text-transform:uppercase;margin:10px 20px 2px 0;letter-spacing: 0.115em;display:inline-block;'
            . 'font-weight:normal;text-align:center;  vertical-align:middle;cursor:pointer;border:1px solid #000;'
            . 'background:rgba(255, 255, 255, 0.03);}.consentText{display:block}';
        $container = '.contentStyle{max-width:980px;margin-right:auto;margin-left:auto;padding:15px;}';

        yield 'big button top bar' => [
            'bigbutton',
            '1',
            '#cookieChoiceInfo{background-color: #fff;color: #000;left:0;margin:0;padding:4px;position:fixed;'
            . 'text-align:left;top:0;width:100%;z-index:9999;}' . $container . $bigButton,
        ];
        yield 'small button bottom bar' => [
            'smallbutton',
            '3',
            '#cookieChoiceInfo{background-color: #fff;color: #000;left:0;margin:0;padding:4px;position:fixed;'
            . 'text-align:left;bottom:0;width:100%;z-index:9999;}' . $container . $smallButton,
        ];
        yield 'big button fallback bar ignores the alignment' => [
            'bigbutton',
            '9',
            '#cookieChoiceInfo{background-color: #fff;color: #000;left:0;margin:0;padding:4px;position:fixed;'
            . 'text-align:center;top:0;width:100%;z-index:9999;}' . $bigButton,
        ];
        yield 'big button dialog' => [
            'bigbutton',
            '2',
            '.glassStyle{position:fixed;width:100%;height:100%;z-index:999;top:0;left:0;opacity:0.5;'
            . 'filter:alpha(opacity=50);background-color:#ccc;}.bannerStyle{min-width:100%;z-index:9999;'
            . 'position:fixed;top:25%;}.contentStyle{position:relative;background-color:#fff;padding:20px;'
            . 'box-shadow:4px 4px 25px #888;max-width:80%;margin:0 auto;}' . $bigButton,
        ];
        yield 'default template' => [
            'default',
            '1',
            '#cookieChoiceInfo{background-color: #fff;color: #000;left:0;margin:0;padding:4px;position:fixed;'
            . 'text-align:center;top:0;width:100%;z-index:9999;}' . self::DEFAULT_BUTTON_STYLE,
        ];
    }

    /**
     * @dataProvider templates
     */
    public function testTemplatesChangeTheStyle(string $template, string $banner, string $style): void
    {
        $output = $this->render(['js_template' => $template, 'banner' => $banner] + $this->options());

        $this->assertStringContainsString('<style type="text/css">' . $style . '</style>', $output);
    }

    public function customCssBanners(): iterable
    {
        yield 'top bar' => ['1'];
        yield 'dialog' => ['2'];
        yield 'bottom bar' => ['3'];
        yield 'fallback bar' => ['9'];
    }

    /**
     * @dataProvider customCssBanners
     */
    public function testTheCustomTemplateUsesTheCustomCss(string $banner): void
    {
        $output = $this->render([
            'js_template' => 'custom',
            'banner' => $banner,
            'customCSS' => '#cookieChoiceInfo{top:0} a"b',
        ] + $this->options());

        $this->assertStringContainsString(
            '<style type="text/css">#cookieChoiceInfo{top:0} a&quot;b</style>',
            $output
        );
    }

    public function testTheCustomTemplateWithoutCssPrintsAnEmptyStyle(): void
    {
        $output = $this->render(['js_template' => 'custom'] + $this->options());

        $this->assertStringContainsString('<style type="text/css"></style>', $output);
    }

    public function testColorsAndClassesComeFromTheOptions(): void
    {
        $output = $this->render([
            'banner_bg' => '#111111',
            'banner_text_color' => '#222222',
            'bannerStyle' => 'my-banner',
            'contentStyle' => 'my-content',
            'consentText' => 'my-text',
            'infoClass' => 'my-info',
            'closeClass' => 'my-close',
        ] + $this->options());

        $this->assertStringContainsString(
            '#cookieChoiceInfo{background-color: #111111;color: #222222;left:0;margin:0;padding:4px;position:fixed;'
            . 'text-align:center;top:0;width:100%;z-index:9999;}.my-info,.my-close{margin-left:10px;}',
            $output
        );
        $this->assertStringContainsString(
            'infoClass="my-info",closeClass="my-close",htmlM="",rel="",tar="",bgB="#111111",btcB="#222222",'
            . 'bPos="top:0",bannerStyle="my-banner",contentStyle="my-content",consText="my-text"',
            $output
        );
        $this->assertStringContainsString('class="my-info" target="_blank"', $output);
    }

    public function testEmptyColorsAndClassesFallBackToTheDefaults(): void
    {
        $output = $this->render([
            'banner_bg' => '',
            'banner_text_color' => '',
            'bannerStyle' => '',
            'contentStyle' => '',
            'consentText' => '',
            'infoClass' => '',
            'closeClass' => '',
        ] + $this->options());

        $this->assertStringContainsString(
            'infoClass="italybtn",closeClass="italybtn",htmlM="",rel="",tar="",bgB="#fff",btcB="#000",'
            . 'bPos="top:0",bannerStyle="bannerStyle",contentStyle="contentStyle",consText="consentText"',
            $output
        );
    }

    public function testCookieNameAndValueComeFromTheOptions(): void
    {
        $output = $this->render(['cookie_name' => 'myConsent', 'cookie_value' => 'accepted'] + $this->options());

        $this->assertStringContainsString('var coNA="myConsent",coVA="accepted";', $output);
    }

    public function testCookieNameAndValueHaveDefaults(): void
    {
        $options = $this->options();
        unset($options['cookie_name'], $options['cookie_value']);

        $this->assertStringContainsString('var coNA="displayCookieConsent",coVA="y";', $this->render($options));
    }

    public function testBehaviourFlagsArePassedToTheScript(): void
    {
        $output = $this->render(['scroll' => '1', 'reload' => '1', 'target' => '1'] + $this->options());

        $this->assertStringContainsString('scroll="1",elPos="fixed"', $output);
        $this->assertStringContainsString('rel="1",tar="1"', $output);
    }

    public function testScrollIsDisabledOnThePolicyPage(): void
    {
        $page = self::factory()->post->create(['post_type' => 'page', 'post_name' => 'privacy-policy']);
        $this->go_to(\get_permalink($page));
        $_COOKIE['displayCookieConsent'] = 'y';
        $sut = $this->makeCookieChoices(['scroll' => '1', 'slug' => 'privacy-policy'] + $this->options());
        $sut->run();

        $output = $this->capture($sut);

        $this->assertStringContainsString('scroll="",elPos="fixed"', $output);
    }

    public function testTheDialogIsNotUsedOnThePolicyPage(): void
    {
        $post = self::factory()->post->create(['post_name' => 'privacy-policy']);
        $this->go_to(\get_permalink($post));
        $_COOKIE['displayCookieConsent'] = 'y';
        $sut = $this->makeCookieChoices(['banner' => '2', 'slug' => 'privacy-policy'] + $this->options());
        $sut->run();

        $output = $this->capture($sut);

        $this->assertStringContainsString('cookieChoices.showCookieConsentBar(', $output);
        $this->assertStringNotContainsString('<style type="text/css">.glassStyle', $output);
    }

    public function testScrollIsKeptOnOtherPages(): void
    {
        $this->go_to(\get_permalink(self::factory()->post->create(['post_type' => 'page'])));
        $_COOKIE['displayCookieConsent'] = 'y';
        $sut = $this->makeCookieChoices(['scroll' => '1', 'slug' => 'privacy-policy'] + $this->options());
        $sut->run();

        $this->assertStringContainsString('scroll="1"', $this->capture($sut));
    }

    /**
     * Current behaviour: without a slug the policy page defaults to the post with ID 1.
     */
    public function testTheDefaultPolicyPageIsThePostWithIdOne(): void
    {
        $this->assertSame(1, self::factory()->post->create(['import_id' => 1]));
        $this->go_to(\get_permalink(1));
        $_COOKIE['displayCookieConsent'] = 'y';
        $sut = $this->makeCookieChoices(['scroll' => '1'] + $this->options());
        $sut->run();

        $this->assertStringContainsString('scroll="",elPos="fixed"', $this->capture($sut));
    }

    public function missingSlugs(): iterable
    {
        yield 'no slug' => [[]];
        yield 'empty slug' => [['slug' => '']];
    }

    /**
     * @dataProvider missingSlugs
     */
    public function testScrollIsKeptOnOtherPagesWithoutSlug(array $slug): void
    {
        $this->go_to(\get_permalink(self::factory()->post->create(['post_type' => 'page'])));
        $_COOKIE['displayCookieConsent'] = 'y';
        $sut = $this->makeCookieChoices(['scroll' => '1'] + $slug + $this->options());
        $sut->run();

        $this->assertStringContainsString('scroll="1",elPos="fixed"', $this->capture($sut));
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

    public function testTextsAreSanitized(): void
    {
        $output = $this->render([
            'text' => 'It\'s <a href="https://example.com/policy">our policy</a><script>alert(\'xss\')</script>',
            'url' => 'javascript:alert(1)',
            'anchor_text' => 'Read "more"',
            'button_text' => "Ok, I'm in",
        ] + $this->options());

        $this->assertStringContainsString(
            'cookieChoices.showCookieConsentBar(\'It\\\'s <a href="https://example.com/policy">our policy</a>'
            . 'alert(\\\'xss\\\')\', "Ok, I\\\'m in", "Read &quot;more&quot;", "");',
            $output
        );
        $this->assertStringContainsString(
            '<span>It\'s <a href="https://example.com/policy">our policy</a>alert(\'xss\')</span><a href=""',
            $output
        );
    }

    public function testTheJavascriptArrayCanBeFiltered(): void
    {
        \add_filter('icc_js_array', static function (array $scripts): array {
            $scripts[] = '<script src="a.js"></script>';
            return $scripts;
        });

        $this->assertStringContainsString(
            'jsArr = ["<script src=\"a.js\"><\/script>"];',
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
        \add_filter('icc_output_html', static fn(string $html): string => 'filtered:' . \strlen($html));

        $this->assertStringStartsWith('filtered:', $this->render($this->options()));
    }

    private function cookieChoicesScript(): string
    {
        $file = WP_DEBUG ? '/js/default/cookiechoices.js' : '/js/default/cookiechoices.php';

        return (string) \file_get_contents(ITALY_COOKIE_CHOICES_DIRNAME . $file);
    }

    private function options(): array
    {
        return [
            'active' => '1',
            'banner' => '1',
            'text' => 'This site uses cookies',
            'url' => 'http://wordpress/privacy/',
            'anchor_text' => 'Read more',
            'button_text' => 'Accept',
            'cookie_name' => 'displayCookieConsent',
            'cookie_value' => 'y',
        ];
    }

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

    private function makeCookieChoices(array $options): Cookie_Choices
    {
        return new Cookie_Choices(new Config($options), new Cookie());
    }
}
