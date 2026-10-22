<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Consent\Infrastructure\FilteredConsentPolicy;
use ItalyCookieChoices\Settings\Options;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Prints the consent banner and the preferences dialog, hidden until the script finds no consent.
 *
 * The markup is the same for every visitor, so the pages can be cached.
 */
final readonly class Banner implements SubscriberInterface
{
    public const HANDLE = 'italy-cookie-choices-banner';
    public const TEXTS_FILTER = 'italy_cookie_choices_banner_texts';

    private const SCRIPT = 'banner.js';
    private const STYLE = 'banner.css';
    private const ASSET = 'banner.asset.php';
    private const TEMPLATE = 'banner.php';
    private const CUSTOM_TEMPLATE = 'custom';
    private const POSITIONS = ['1' => 'top', '2' => 'modal', '3' => 'bottom'];

    /**
     * @param string $buildPath The assets/build folder, with the trailing slash.
     * @param string $buildUrl The assets/build URL, with the trailing slash.
     * @param string $templatesPath The templates folder, with the trailing slash.
     */
    public function __construct(
        private FilteredConsentPolicy $policy,
        private CategoryLabels $labels,
        private Options $options,
        private string $buildPath,
        private string $buildUrl,
        private string $templatesPath,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_enqueue_scripts' => 'enqueue';
        // Between the body and the footer buffers of the 2.x blocking,
        // its patterns can remove the markup and the script.
        yield 'wp_footer' => [
            self::CALLBACK => 'render',
            self::PRIORITY => -99999,
        ];
    }

    public function enqueue(): void
    {
        $asset = $this->asset();
        if ($asset === null || !$this->isShown()) {
            return;
        }

        \wp_register_script(
            self::HANDLE,
            $this->buildUrl . self::SCRIPT,
            [...$asset['dependencies'], ConsentScript::HANDLE],
            $asset['version'],
            ['in_footer' => true]
        );

        // The custom template has only the custom CSS, without the plugin styles.
        $isCustom = $this->options->text('js_template') === self::CUSTOM_TEMPLATE;
        \wp_register_style(self::HANDLE, $isCustom ? false : $this->buildUrl . self::STYLE, [], $asset['version']);
        \wp_enqueue_style(self::HANDLE);
        \wp_style_add_data(self::HANDLE, 'rtl', 'replace');
        \wp_add_inline_style(self::HANDLE, $this->inlineStyle());
    }

    public function render(): void
    {
        if ($this->asset() === null || !$this->isShown()) {
            return;
        }

        $view = $this->view();
        require $this->templatesPath . self::TEMPLATE;
        \wp_print_scripts([self::HANDLE]);
    }

    /**
     * The data the template prints, not escaped yet.
     *
     * @return array<string, mixed>
     */
    public function view(): array
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
            'reload' => $this->options->isChecked('reload'),
            'htmlMargin' => $position === self::POSITIONS['1'] && $this->options->isChecked('html_margin'),
            'text' => \wp_kses_post($this->translated('Banner text', 'text')),
            'link' => $this->link(),
            'texts' => $this->texts(),
            'categories' => $this->categories(),
        ];
    }

    private function isShown(): bool
    {
        if (!$this->options->isActive() || \is_feed()) {
            return false;
        }

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
        $url = $this->translated('Banner url', 'url');
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
            'text' => $this->translated('Banner anchor text', 'anchor_text', $url),
            'newTab' => $newTab,
            'rel' => \implode(' ', $rel),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function texts(): array
    {
        $texts = [
            'title' => \__('Cookie consent', 'italy-cookie-choices'),
            'acceptAll' => $this->translated(
                'Banner button text',
                'button_text',
                \__('Accept all', 'italy-cookie-choices')
            ),
            'rejectAll' => \__('Reject all', 'italy-cookie-choices'),
            'preferences' => \__('Preferences', 'italy-cookie-choices'),
            'close' => \__('Close and keep only the necessary cookies', 'italy-cookie-choices'),
            'preferencesTitle' => \__('Cookie preferences', 'italy-cookie-choices'),
            'save' => \__('Save preferences', 'italy-cookie-choices'),
            'closePreferences' => \__('Close the preferences', 'italy-cookie-choices'),
        ];

        /**
         * Filters the texts of the banner and of the preferences dialog.
         *
         * @param mixed $texts The texts keyed by name.
         */
        $filtered = \apply_filters(self::TEXTS_FILTER, $texts);
        if (!\is_array($filtered)) {
            return $texts;
        }

        foreach ($texts as $name => $text) {
            $texts[$name] = \is_string($filtered[$name] ?? null) ? $filtered[$name] : $text;
        }

        return $texts;
    }

    /**
     * @return list<array{id: string, required: bool, label: string, description: string}>
     */
    private function categories(): array
    {
        $categories = [];
        foreach ($this->policy->create()->categories() as $category) {
            $categories[] = [
                'id' => $category->id(),
                'required' => $category->isRequired(),
                ...$this->labels->for($category),
            ];
        }

        return $categories;
    }

    private function translated(string $name, string $option, string $default = ''): string
    {
        $value = $this->options->text($option);
        if ($value === '') {
            return $default;
        }

        $translated = \get_string('Italy Cookie Choices', $name, $value);

        return \is_string($translated) && $translated !== '' ? $translated : $value;
    }

    private function inlineStyle(): string
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
     *
     * @return array{dependencies: list<non-empty-string>, version: string}|null
     */
    private function asset(): ?array
    {
        $file = $this->buildPath . self::ASSET;
        if (!\is_readable($file)) {
            return null;
        }

        /** @var array{dependencies: list<non-empty-string>, version: string} */
        return require $file;
    }
}
