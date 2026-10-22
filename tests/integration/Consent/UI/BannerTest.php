<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\UI;

use ItalyCookieChoices\Consent\Infrastructure\FilteredConsentPolicy;
use ItalyCookieChoices\Consent\UI\Banner;
use ItalyCookieChoices\Consent\UI\CategoryLabels;
use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class BannerTest extends WPTestCase
{
    private const BUILD_URL = 'https://example.com/wp-content/plugins/italy-cookie-choices/assets/build/';

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        $GLOBALS['wp_styles'] = new \WP_Styles();
        // Registered by ConsentScript on the same hook.
        \wp_register_script(ConsentScript::HANDLE, false, [], null);
        $this->saveOptions([]);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['wp_scripts'], $GLOBALS['wp_styles']);
        parent::tearDown();
    }

    public function testItPrintsTheBannerOutsideTheLegacyBuffers(): void
    {
        $this->assertSame(
            [
                'wp_enqueue_scripts' => 'enqueue',
                'wp_footer' => [
                    SubscriberInterface::CALLBACK => 'render',
                    SubscriberInterface::PRIORITY => -99999,
                ],
            ],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testItRegistersTheScriptAfterTheConsentApi(): void
    {
        $this->makeSut()->enqueue();

        $script = \wp_scripts()->registered[Banner::HANDLE];
        $this->assertSame(self::BUILD_URL . 'banner.js', $script->src);
        $this->assertSame([ConsentScript::HANDLE], $script->deps);
        $this->assertSame('banner-version', $script->ver);
        $this->assertSame(1, \wp_scripts()->get_data(Banner::HANDLE, 'group'));
    }

    public function testItEnqueuesTheStylesWithTheColorsAndTheCustomCss(): void
    {
        $this->saveOptions(['banner_bg' => '#123456', 'banner_text_color' => 'red', 'customCSS' => '.a{b:c}']);

        $this->makeSut()->enqueue();

        $this->assertTrue(\wp_style_is(Banner::HANDLE, 'enqueued'));
        $this->assertSame(self::BUILD_URL . 'banner.css', \wp_styles()->registered[Banner::HANDLE]->src);
        $this->assertSame('replace', \wp_styles()->get_data(Banner::HANDLE, 'rtl'));
        $this->assertSame(
            ['.icc-banner,.icc-preferences{--icc-background:#123456;--icc-color:#000000;}.a{b:c}'],
            \wp_styles()->get_data(Banner::HANDLE, 'after')
        );
    }

    public function testTheCustomTemplateHasOnlyTheCustomCss(): void
    {
        $this->saveOptions(['js_template' => 'custom', 'customCSS' => '<b>.a{b:c}</b>']);

        $this->makeSut()->enqueue();

        $this->assertFalse(\wp_styles()->registered[Banner::HANDLE]->src);
        $after = \wp_styles()->get_data(Banner::HANDLE, 'after');
        $this->assertIsArray($after);
        $customCss = \end($after);
        $this->assertIsString($customCss);
        $this->assertStringEndsWith('.a{b:c}', $customCss);
    }

    public function testItRendersTheBannerAndPrintsItsScript(): void
    {
        $sut = $this->makeSut();
        $sut->enqueue();

        $output = $this->render($sut);

        $this->assertStringContainsString('id="icc-banner"', $output);
        $this->assertStringContainsString('id="icc-preferences"', $output);
        $this->assertStringContainsString(self::BUILD_URL . 'banner.js?ver=banner-version', $output);
    }

    public function testNothingIsPrintedWhenThePluginIsNotActive(): void
    {
        $this->saveOptions(['active' => null]);
        $sut = $this->makeSut();

        $sut->enqueue();

        $this->assertSame('', $this->render($sut));
        $this->assertFalse(\wp_script_is(Banner::HANDLE, 'registered'));
    }

    public function testNothingIsPrintedWhenTheAssetsAreNotBuilt(): void
    {
        $sut = $this->makeSut('/not/built/');

        $sut->enqueue();

        $this->assertSame('', $this->render($sut));
        $this->assertFalse(\wp_script_is(Banner::HANDLE, 'registered'));
    }

    public function testNothingIsPrintedInTheFeeds(): void
    {
        $this->go_to(\get_feed_link());

        $this->assertSame('', $this->render($this->makeSut()));
    }

    public function testNothingIsPrintedOnThePolicyPage(): void
    {
        $id = $this->createPage('cookie-policy');
        $this->saveOptions(['slug' => 'cookie-policy']);
        $this->go_to(\get_permalink($id));

        $this->assertSame('', $this->render($this->makeSut()));
    }

    public function testNothingIsPrintedOnTheExcludedPagesByIdOrSlug(): void
    {
        $byId = $this->createPage('contact');
        $bySlug = $this->createPage('about');
        $other = $this->createPage('other');
        $this->saveOptions(['excluded_pages' => $byId . ', about,,']);

        $this->go_to(\get_permalink($byId));
        $this->assertSame('', $this->render($this->makeSut()));

        $this->go_to(\get_permalink($bySlug));
        $this->assertSame('', $this->render($this->makeSut()));

        $this->go_to(\get_permalink($other));
        $this->assertStringContainsString('id="icc-banner"', $this->render($this->makeSut()));
    }

    public function testTheViewHasTheDefaults(): void
    {
        $view = $this->makeSut()->view();

        $this->assertSame(
            [
                'banner' => 'icc-banner icc-banner--top icc-banner--default',
                'content' => 'icc-banner__content',
                'text' => 'icc-banner__text',
                'link' => '',
                'button' => 'icc-banner__button',
            ],
            $view['classes']
        );
        $this->assertFalse($view['modal']);
        $this->assertFalse($view['reload']);
        $this->assertFalse($view['htmlMargin']);
        $this->assertSame('', $view['text']);
        $this->assertNull($view['link']);
        $this->assertSame('Accept all', $this->texts($view)['acceptAll']);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public function positions(): iterable
    {
        yield 'top bar' => ['1', 'top', false];
        yield 'dialog' => ['2', 'modal', true];
        yield 'bottom bar' => ['3', 'bottom', false];
        yield 'unknown' => ['4', 'top', false];
    }

    /**
     * @dataProvider positions
     */
    public function testThePositionComesFromTheSettings(string $banner, string $position, bool $modal): void
    {
        $this->saveOptions(['banner' => $banner, 'js_template' => 'bigbutton']);

        $view = $this->makeSut()->view();

        $this->assertIsArray($view['classes']);
        $this->assertSame(
            \sprintf('icc-banner icc-banner--%s icc-banner--bigbutton', $position),
            $view['classes']['banner']
        );
        $this->assertSame($modal, $view['modal']);
    }

    public function testTheHtmlMarginIsOnlyForTheTopBar(): void
    {
        $this->saveOptions(['html_margin' => '1']);
        $this->assertTrue($this->makeSut()->view()['htmlMargin']);

        $this->saveOptions(['html_margin' => '1', 'banner' => '3']);
        $this->assertFalse($this->makeSut()->view()['htmlMargin']);
    }

    public function testTheViewUsesTheSavedTextsAndClasses(): void
    {
        $this->saveOptions([
            'text' => 'We use <strong>cookies</strong><script>x</script>',
            'url' => 'https://example.com/policy/',
            'anchor_text' => 'Read more',
            'button_text' => 'OK',
            'reload' => '1',
            'bannerStyle' => 'my-banner',
            'contentStyle' => 'my-content',
            'consentText' => 'my-text',
            'infoClass' => 'my-link',
            'closeClass' => 'my-button',
        ]);

        $view = $this->makeSut()->view();

        $this->assertSame('We use <strong>cookies</strong>x', $view['text']);
        $this->assertSame(
            ['url' => 'https://example.com/policy/', 'text' => 'Read more', 'newTab' => false, 'rel' => ''],
            $view['link']
        );
        $this->assertSame('OK', $this->texts($view)['acceptAll']);
        $this->assertTrue($view['reload']);
        $this->assertSame(
            [
                'banner' => 'icc-banner icc-banner--top icc-banner--default my-banner',
                'content' => 'icc-banner__content my-content',
                'text' => 'icc-banner__text my-text',
                'link' => 'my-link',
                'button' => 'icc-banner__button my-button',
            ],
            $view['classes']
        );
    }

    public function testTheLinkTextFallsBackToTheUrl(): void
    {
        $this->saveOptions(['url' => 'https://example.com/policy/']);

        $link = $this->makeSut()->view()['link'];

        $this->assertIsArray($link);
        $this->assertSame('https://example.com/policy/', $link['text']);
    }

    /**
     * @return iterable<string, array{array<string, string>, bool, string}>
     */
    public function linkAttributes(): iterable
    {
        yield 'new tab' => [['target' => '1'], true, 'noopener'];
        yield 'nofollow' => [['nofollow' => '1'], false, 'nofollow'];
        yield 'both' => [['target' => '1', 'nofollow' => '1'], true, 'noopener nofollow'];
    }

    /**
     * @dataProvider linkAttributes
     * @param array<string, string> $options
     */
    public function testTheLinkAttributesComeFromTheSettings(array $options, bool $newTab, string $rel): void
    {
        $this->saveOptions(['url' => 'https://example.com/policy/'] + $options);

        $link = $this->makeSut()->view()['link'];

        $this->assertIsArray($link);
        $this->assertSame($newTab, $link['newTab']);
        $this->assertSame($rel, $link['rel']);
    }

    public function testTheTextsCanBeFiltered(): void
    {
        \add_filter(Banner::TEXTS_FILTER, static fn(array $texts): array => [
            'rejectAll' => 'Only necessary',
            'title' => ['not a string'],
            'unknown' => 'ignored',
        ] + $texts);

        $texts = $this->texts($this->makeSut()->view());

        $this->assertSame('Only necessary', $texts['rejectAll']);
        $this->assertSame('Cookie consent', $texts['title']);
        $this->assertArrayNotHasKey('unknown', $texts);
    }

    public function testAFilterThatReturnsNoArrayKeepsTheTexts(): void
    {
        \add_filter(Banner::TEXTS_FILTER, '__return_null');

        $this->assertSame('Reject all', $this->texts($this->makeSut()->view())['rejectAll']);
    }

    public function testTheCategoriesHaveTheirLabels(): void
    {
        \add_filter(FilteredConsentPolicy::CATEGORIES_FILTER, static fn(): array => [['id' => 'social']]);

        $categories = $this->makeSut()->view()['categories'];

        $this->assertIsArray($categories);
        $this->assertSame(
            [
                [
                    'id' => 'necessary',
                    'required' => true,
                    'label' => 'Necessary',
                    'description' => 'Needed for the site to work, like the cookie that stores this choice.'
                        . ' Always active.',
                ],
                ['id' => 'social', 'required' => false, 'label' => 'social', 'description' => ''],
            ],
            $categories
        );
    }

    public function testTheCategoryLabelsCanBeFiltered(): void
    {
        \add_filter(CategoryLabels::FILTER, static fn(array $labels): array => [
            'statistics' => ['label' => 'Analytics', 'description' => 'Visits'],
            'marketing' => 'invalid',
        ] + $labels);

        $categories = $this->makeSut()->view()['categories'];

        $this->assertIsArray($categories);
        $this->assertSame(
            ['id' => 'statistics', 'required' => false, 'label' => 'Analytics', 'description' => 'Visits'],
            $categories[2]
        );
        $this->assertSame(
            ['id' => 'marketing', 'required' => false, 'label' => 'marketing', 'description' => ''],
            $categories[3]
        );
    }

    public function testTheTemplatePrintsAnAccessibleDialog(): void
    {
        $this->saveOptions(['text' => 'We use cookies', 'url' => 'https://example.com/policy/', 'banner' => '2']);

        $output = $this->render($this->makeSut());

        $this->assertStringContainsString('role="dialog"', $output);
        $this->assertStringContainsString('aria-modal="true"', $output);
        $this->assertStringContainsString('aria-labelledby="icc-banner-title"', $output);
        $this->assertStringContainsString('aria-describedby="icc-banner-text"', $output);
        $this->assertStringContainsString('aria-controls="icc-preferences"', $output);
        $this->assertStringContainsString('aria-label="Close and keep only the necessary cookies"', $output);
        $this->assertStringContainsString('id="icc-category-necessary"', $output);
        $this->assertStringContainsString('aria-describedby="icc-category-necessary-description"', $output);
        $this->assertSame(1, \substr_count($output, 'checked disabled'));
        $this->assertStringContainsString('href="https://example.com/policy/"', $output);
    }

    /**
     * @param array<string, string|null> $options
     */
    private function saveOptions(array $options): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(
            Schema::OPTION,
            \array_filter($options + ['active' => '1'], static fn(?string $value): bool => $value !== null)
        );
    }

    private function createPage(string $slug): int
    {
        $id = self::factory()->post->create(['post_type' => 'page', 'post_name' => $slug]);
        $this->assertIsInt($id);

        return $id;
    }

    /**
     * @phpstan-impure
     */
    private function render(Banner $sut): string
    {
        \ob_start();
        $sut->render();

        return (string) \ob_get_clean();
    }

    /**
     * @param array<string, mixed> $view
     * @return array<array-key, mixed>
     */
    private function texts(array $view): array
    {
        $this->assertIsArray($view['texts']);

        return $view['texts'];
    }

    private function makeSut(string $buildPath = ''): Banner
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $root = \codecept_root_dir('templates/');
        $this->assertIsString($fixtures);
        $this->assertIsString($root);

        return new Banner(
            new FilteredConsentPolicy(),
            new CategoryLabels(),
            new Options(),
            $buildPath === '' ? $fixtures : $buildPath,
            self::BUILD_URL,
            $root
        );
    }
}
