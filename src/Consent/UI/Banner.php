<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Settings\Options;
use ItalyStrap\Asset\AssetLoader;
use ItalyStrap\Asset\BuildMetadata;
use ItalyStrap\Asset\BuildMetadataFile;
use ItalyStrap\Asset\Dependencies;
use ItalyStrap\Asset\Handle;
use ItalyStrap\Asset\Hook;
use ItalyStrap\Asset\RegisterOnly;
use ItalyStrap\Asset\Script;
use ItalyStrap\Asset\ScriptLoading;
use ItalyStrap\Asset\Style;
use ItalyStrap\Event\SubscriberInterface;
use ItalyStrap\Config\Config;
use ItalyStrap\View\ViewInterface;
use SplFileInfo;

/**
 * Prints the consent banner and the preferences dialog, hidden until the script finds no consent.
 * On the policy page and on the excluded pages the banner does not open by itself,
 * the visitor can still open the preferences there to change or withdraw the consent.
 *
 * The markup is the same for every visitor, so the pages can be cached.
 */
final readonly class Banner implements SubscriberInterface
{
    public const HANDLE = 'italy-cookie-choices-banner';

    private const SCRIPT = 'banner.js';
    private const STYLE = 'banner.css';
    private const TEMPLATE = 'banner';
    private const CUSTOM_TEMPLATE = 'custom';
    private const POSITIONS = ['1' => 'top', '2' => 'modal', '3' => 'bottom'];

    /**
     * @param string $buildPath The assets/build folder, with the trailing slash.
     * @param string $buildUrl The assets/build URL, with the trailing slash.
     */
    public function __construct(
        private ConsentTexts $texts,
        private Options $options,
        private ViewInterface $view,
        private AssetLoader $assets,
        private string $buildPath,
        private string $buildUrl,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield Hook::FRONT_END => 'enqueue';
        // Before the footer scripts, the banner is the first thing the keyboard reaches.
        yield 'wp_footer' => [
            self::CALLBACK => 'render',
            self::PRIORITY => -99999,
        ];
    }

    public function enqueue(): void
    {
        $metadata = $this->metadata();
        if ($metadata === null || !$this->isPrinted()) {
            return;
        }

        // The custom template has only the custom CSS, without the plugin styles.
        $isCustom = $this->options->text('js_template') === self::CUSTOM_TEMPLATE;

        $this->assets->load(
            // Only registered, render() prints it after the markup
            new Script(
                handle: new Handle(self::HANDLE),
                source: $this->buildUrl . self::SCRIPT,
                dependencies: $metadata->dependencies()->with(new Dependencies(ConsentScript::HANDLE)),
                version: $metadata->version(),
                loading: new ScriptLoading(inFooter: true),
                condition: new RegisterOnly(),
            ),
            new Style(
                handle: new Handle(self::HANDLE),
                source: $isCustom ? null : $this->buildUrl . self::STYLE,
                version: $metadata->version(),
                inlineStyles: [$this->inlineStyle()],
                data: ['rtl' => 'replace'],
            ),
        );
    }

    public function render(): void
    {
        if ($this->metadata() === null || !$this->isPrinted()) {
            return;
        }

        echo $this->html(); // phpcs:ignore -- Escaped in the template.
        \wp_print_scripts([self::HANDLE]);
    }

    /**
     * The markup of the banner and the preferences dialog, both hidden.
     */
    public function html(): string
    {
        return $this->view->render(self::TEMPLATE, new Config($this->data()));
    }

    /**
     * The URL of the plugin styles, null for the custom template that has only the custom CSS.
     */
    public function stylesheet(): ?string
    {
        $metadata = $this->metadata();
        if ($metadata === null || $this->options->text('js_template') === self::CUSTOM_TEMPLATE) {
            return null;
        }

        return \add_query_arg('ver', $metadata->version()->value(), $this->buildUrl . self::STYLE);
    }

    /**
     * The data the template prints, not escaped yet.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $position = self::POSITIONS[$this->options->text('banner', '1')] ?? self::POSITIONS['1'];
        $template = $this->options->text('js_template', 'default');

        return [
            'classes' => [
                'banner' => \trim(\sprintf(
                    'icc-banner icc-banner--%s icc-banner--%s %s',
                    $position,
                    $template,
                    $this->options->text('bannerStyle')
                )),
                'content' => \trim('icc-banner__content ' . $this->options->text('contentStyle')),
                'text' => \trim('icc-banner__text ' . $this->options->text('consentText')),
                'link' => $this->options->text('infoClass'),
                'button' => \trim('icc-banner__button ' . $this->options->text('closeClass')),
            ],
            'modal' => $position === self::POSITIONS['2'],
            'opensByItself' => $this->opensByItself(),
            'reload' => $this->options->isChecked('reload'),
            'htmlMargin' => $position === self::POSITIONS['1'] && $this->options->isChecked('html_margin'),
            'text' => \wp_kses_post($this->options->translated('text')),
            'link' => $this->link(),
            ...$this->texts->all(),
        ];
    }

    private function isPrinted(): bool
    {
        return $this->options->isActive() && !\is_feed();
    }

    private function opensByItself(): bool
    {
        $policyPage = $this->options->text('slug');
        if ($policyPage !== '' && (\is_page($policyPage) || \is_single($policyPage))) {
            return false;
        }

        $excludedPages = $this->excludedPages();

        return $excludedPages === [] || !\is_page($excludedPages);
    }

    /**
     * @return list<int|string>
     */
    private function excludedPages(): array
    {
        $pages = [];
        foreach (\explode(',', $this->options->text('excluded_pages')) as $page) {
            $page = \trim($page);
            if ($page === '') {
                continue;
            }

            $pages[] = \ctype_digit($page) ? (int) $page : $page;
        }

        return $pages;
    }

    /**
     * @return array{url: string, text: string, newTab: bool, rel: string}|null
     */
    private function link(): ?array
    {
        $url = $this->options->translated('url');
        if ($url === '') {
            return null;
        }

        $newTab = $this->options->isChecked('target');
        $rel = \array_filter([
            $newTab ? 'noopener' : '',
            $this->options->isChecked('nofollow') ? 'nofollow' : '',
        ]);

        return [
            'url' => $url,
            'text' => $this->options->translated('anchor_text', $url),
            'newTab' => $newTab,
            'rel' => \implode(' ', $rel),
        ];
    }

    /**
     * The colors of the banner and the custom CSS.
     */
    public function inlineStyle(): string
    {
        $background = \sanitize_hex_color($this->options->text('banner_bg')) ?? '#ffffff';
        $color = \sanitize_hex_color($this->options->text('banner_text_color')) ?? '#000000';

        return \sprintf(
            '.icc-banner,.icc-preferences{--icc-background:%s;--icc-color:%s;}%s',
            $background,
            $color,
            \wp_strip_all_tags($this->options->text('customCSS'))
        );
    }

    /**
     * The dependencies and the version generated by the build, null when the assets are not built.
     */
    private function metadata(): ?BuildMetadata
    {
        $file = new BuildMetadataFile(new SplFileInfo($this->buildPath . self::SCRIPT));

        return $file->exists() ? $file->read() : null;
    }
}
