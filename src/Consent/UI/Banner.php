<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Blocking\Application\VendorChoices;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Asset\AssetLoader;
use ItalyStrap\Asset\BuildMetadata;
use ItalyStrap\Asset\BuildMetadataFile;
use ItalyStrap\Asset\Dependencies;
use ItalyStrap\Asset\EventName;
use ItalyStrap\Asset\Handle;
use ItalyStrap\Asset\RegisterOnly;
use ItalyStrap\Asset\Script;
use ItalyStrap\Asset\ScriptLoader;
use ItalyStrap\Asset\ScriptLoading;
use ItalyStrap\Asset\Style;
use ItalyStrap\Config\Config;
use ItalyStrap\Event\SubscriberInterface;
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
    private const POSITIONS = ['1' => 'top', '2' => 'modal', '3' => 'bottom', '4' => 'box-left', '5' => 'box-right'];

    private const FLOATING_BUTTON_POSITIONS = ['left', 'right'];

    /**
     * @param string $buildPath The assets/build folder, with the trailing slash.
     * @param string $buildUrl The assets/build URL, with the trailing slash.
     */
    public function __construct(
        private ConsentTexts $texts,
        private Options $options,
        private VendorChoices $vendors,
        private ViewInterface $view,
        private AssetLoader $assets,
        private ScriptLoader $scripts,
        private string $buildPath,
        private string $buildUrl,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield EventName::FRONT_END => 'enqueue';
        // Before the footer scripts, the banner is the first thing the keyboard reaches.
        yield Hook::FOOTER => [
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
        $isCustom = $this->options->text(ConsentSetting::TEMPLATE) === self::CUSTOM_TEMPLATE;

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
        $this->scripts->print(new Handle(self::HANDLE));
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
        if ($metadata === null || $this->options->text(ConsentSetting::TEMPLATE) === self::CUSTOM_TEMPLATE) {
            return null;
        }

        return \add_query_arg('ver', $metadata->version()->value(), $this->buildUrl . self::STYLE);
    }

    /**
     * The vendors of each category, for the consent per vendor, empty when the consent is by category.
     *
     * @return array<string, list<array{id: string, name: string}>>
     */
    private function vendorsByCategory(): array
    {
        $vendors = [];
        foreach ($this->vendors->all() as $vendor) {
            $vendors[$vendor['category']][] = ['id' => $vendor['id'], 'name' => $vendor['name']];
        }

        return $vendors;
    }

    /**
     * The data the template prints, not escaped yet.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $position = self::POSITIONS[$this->options->text(ConsentSetting::POSITION, '1')] ?? self::POSITIONS['1'];
        $template = $this->options->text(ConsentSetting::TEMPLATE, 'default');

        return [
            'classes' => [
                'banner' => \trim(\sprintf(
                    'icc-banner icc-banner--%s icc-banner--%s %s',
                    $position,
                    $template,
                    $this->options->text(ConsentSetting::BANNER_CLASS)
                )),
                'content' => \trim('icc-banner__content ' . $this->options->text(ConsentSetting::CONTENT_CLASS)),
                'text' => \trim('icc-banner__text ' . $this->options->text(ConsentSetting::TEXT_CLASS)),
                'link' => $this->options->text(ConsentSetting::LINK_CLASS),
                'button' => \trim('icc-banner__button ' . $this->options->text(ConsentSetting::BUTTON_CLASS)),
            ],
            'modal' => $position === self::POSITIONS['2'],
            'opensByItself' => $this->opensByItself(),
            'reload' => $this->options->isChecked(ConsentSetting::RELOAD),
            'globalPrivacyControl' => $this->options->isChecked(ConsentSetting::GLOBAL_PRIVACY_CONTROL),
            'htmlMargin' => $position === self::POSITIONS['1']
                && $this->options->isChecked(ConsentSetting::HTML_MARGIN),
            'text' => \wp_kses_post($this->options->translated(ConsentSetting::TEXT)),
            'link' => $this->link(BannerLink::Policy),
            'legalNoticeLink' => $this->link(BannerLink::LegalNotice),
            'floatingButton' => $this->floatingButton(),
            'logo' => $this->logo(),
            'vendors' => $this->vendorsByCategory(),
            ...$this->texts->all(),
        ];
    }

    /**
     * The image of the media library chosen as logo, empty without one or when it is not an image.
     */
    private function logo(): string
    {
        $id = (int) $this->options->text(ConsentSetting::LOGO);
        if ($id <= 0) {
            return '';
        }

        return \wp_get_attachment_image($id, 'medium', false, [
            'class' => 'icc-banner__logo',
            'loading' => false,
            'decoding' => 'async',
        ]);
    }

    /**
     * The button always there after a choice, to change or withdraw the consent, null when it is off.
     *
     * @return array{position: string, label: string}|null
     */
    private function floatingButton(): ?array
    {
        if (!$this->options->isChecked(ConsentSetting::FLOATING_BUTTON)) {
            return null;
        }

        $position = $this->options->text(ConsentSetting::FLOATING_BUTTON_POSITION);

        return [
            'position' => \in_array($position, self::FLOATING_BUTTON_POSITIONS, true) ? $position : 'left',
            'label' => $this->options->translated(
                ConsentSetting::FLOATING_BUTTON_LABEL,
                \__('Cookie preferences', 'italy-cookie-choices')
            ),
        ];
    }

    private function isPrinted(): bool
    {
        return $this->options->isActive() && !\is_feed();
    }

    private function opensByItself(): bool
    {
        foreach (BannerLink::cases() as $link) {
            if ($this->isOnThePageOf($link)) {
                return false;
            }
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
        foreach (\explode(',', $this->options->text(ConsentSetting::EXCLUDED_PAGES)) as $page) {
            $page = \trim($page);
            if ($page === '') {
                continue;
            }

            $pages[] = \ctype_digit($page) ? (int) $page : $page;
        }

        return $pages;
    }

    /**
     * On the page of a link the banner does not open by itself, so the visitor can read it.
     */
    private function isOnThePageOf(BannerLink $link): bool
    {
        $page = $this->options->text($link->pageSetting());

        return $page !== '' && (\is_page($page) || \is_single($page));
    }

    /**
     * The policy and the legal notice links share the new tab and the nofollow settings.
     *
     * @return array{url: string, text: string, newTab: bool, rel: string}|null
     */
    private function link(BannerLink $link): ?array
    {
        $url = $this->options->translated($link->urlSetting());
        if ($url === '') {
            return null;
        }

        $newTab = $this->options->isChecked(ConsentSetting::LINKS_IN_NEW_TAB);
        $rel = \array_filter([
            $newTab ? 'noopener' : '',
            $this->options->isChecked(ConsentSetting::LINKS_NOFOLLOW) ? 'nofollow' : '',
        ]);

        return [
            'url' => $url,
            'text' => $this->options->translated($link->textSetting(), $url),
            'newTab' => $newTab,
            'rel' => \implode(' ', $rel),
        ];
    }

    /**
     * The colors of the banner and the custom CSS.
     */
    public function inlineStyle(): string
    {
        $background = \sanitize_hex_color($this->options->text(ConsentSetting::BACKGROUND_COLOR)) ?? '#ffffff';
        $color = \sanitize_hex_color($this->options->text(ConsentSetting::TEXT_COLOR)) ?? '#000000';

        return \sprintf(
            '.icc-banner,.icc-preferences,.icc-floating-button{--icc-background:%s;--icc-color:%s;}%s',
            $background,
            $color,
            \wp_strip_all_tags($this->options->text(ConsentSetting::CUSTOM_CSS))
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
