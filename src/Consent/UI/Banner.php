<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Settings\Options;
use ItalyStrap\Event\SubscriberInterface;
use ItalyStrap\Config\Config;
use ItalyStrap\View\ViewInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Prints the consent banner and the preferences dialog, hidden until the script finds no consent.
 *
 * The markup is the same for every visitor, so the pages can be cached.
 */
final readonly class Banner implements SubscriberInterface
{
    public const HANDLE = 'italy-cookie-choices-banner';

    private const SCRIPT = 'banner.js';
    private const STYLE = 'banner.css';
    private const ASSET = 'banner.asset.php';
    private const TEMPLATE = 'banner';
    private const CUSTOM_TEMPLATE = 'custom';
    private const POSITIONS = ['1' => 'top', '2' => 'modal', '3' => 'bottom'];

    /**
     * @param string $buildPath The assets/build folder, with the trailing slash.
     * @param string $buildUrl The assets/build URL, with the trailing slash.
     */
    public function __construct(
        private ConsentPolicyFactory $policy,
        private EventDispatcherInterface $dispatcher,
        private Options $options,
        private ViewInterface $view,
        private string $buildPath,
        private string $buildUrl,
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

        echo $this->view->render(self::TEMPLATE, new Config($this->data())); // phpcs:ignore -- Escaped in the template.
        \wp_print_scripts([self::HANDLE]);
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
            'reload' => $this->options->isChecked('reload'),
            'htmlMargin' => $position === self::POSITIONS['1'] && $this->options->isChecked('html_margin'),
            'text' => \wp_kses_post($this->translated('Banner text', 'text')),
            'link' => $this->link(),
            ...$this->content(),
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
     * The texts and the categories, after the listeners of BuildingBanner.
     *
     * @return array{
     *     texts: array<string, string>,
     *     categories: list<array{id: string, required: bool, label: string, description: string}>
     * }
     */
    private function content(): array
    {
        $event = new BuildingBanner($this->defaultTexts(), $this->defaultCategoryLabels());
        $this->dispatcher->dispatch($event);

        $categories = [];
        foreach ($this->policy->create()->categories() as $category) {
            $categories[] = [
                'id' => $category->id(),
                'required' => $category->isRequired(),
                ...$event->categoryLabel($category),
            ];
        }

        return ['texts' => $event->texts(), 'categories' => $categories];
    }

    /**
     * @return array<string, string>
     */
    private function defaultTexts(): array
    {
        return [
            BuildingBanner::TITLE => \__('Cookie consent', 'italy-cookie-choices'),
            BuildingBanner::ACCEPT_ALL => $this->translated(
                'Banner button text',
                'button_text',
                \__('Accept all', 'italy-cookie-choices')
            ),
            BuildingBanner::REJECT_ALL => \__('Reject all', 'italy-cookie-choices'),
            BuildingBanner::PREFERENCES => \__('Preferences', 'italy-cookie-choices'),
            BuildingBanner::CLOSE => \__('Close and keep only the necessary cookies', 'italy-cookie-choices'),
            BuildingBanner::PREFERENCES_TITLE => \__('Cookie preferences', 'italy-cookie-choices'),
            BuildingBanner::SAVE => \__('Save preferences', 'italy-cookie-choices'),
            BuildingBanner::CLOSE_PREFERENCES => \__('Close the preferences', 'italy-cookie-choices'),
        ];
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    private function defaultCategoryLabels(): array
    {
        return [
            Category::NECESSARY => [
                'label' => \__('Necessary', 'italy-cookie-choices'),
                'description' => \__(
                    'Needed for the site to work, like the cookie that stores this choice. Always active.',
                    'italy-cookie-choices'
                ),
            ],
            Category::PREFERENCES => [
                'label' => \__('Preferences', 'italy-cookie-choices'),
                'description' => \__('Remember your choices, like the language or the region.', 'italy-cookie-choices'),
            ],
            Category::STATISTICS => [
                'label' => \__('Statistics', 'italy-cookie-choices'),
                'description' => \__(
                    'Help to understand how the site is used, collecting anonymous data.',
                    'italy-cookie-choices'
                ),
            ],
            Category::MARKETING => [
                'label' => \__('Marketing', 'italy-cookie-choices'),
                'description' => \__(
                    'Used by third parties, like videos, maps and social networks, to show content and ads.',
                    'italy-cookie-choices'
                ),
            ],
        ];
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
