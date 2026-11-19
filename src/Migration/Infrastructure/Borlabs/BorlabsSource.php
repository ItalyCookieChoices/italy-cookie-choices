<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\Borlabs;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Migration\Application\Conversions;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Settings\PluginSetting;
use ItalyStrap\Config\Config;

/**
 * Imports the settings of Borlabs Cookie 3: the dialog, its texts and style, the widget, and its services.
 *
 * Borlabs Cookie is a paid plugin: the import reads only its settings in the database, nothing of it is used.
 *
 * @phpstan-type Data Config<array-key, mixed>
 */
final readonly class BorlabsSource implements MigrationSource
{
    public const ID = 'borlabs-cookie';
    public const NAME = 'Borlabs Cookie';

    private const BAR = ['top' => '1', 'bottom' => '3'];
    private const BOX = ['bottom-left' => '4', 'bottom-right' => '5'];
    private const MODAL = '2';

    public function __construct(
        private BorlabsStorage $storage,
        private BorlabsServices $services,
        private Conversions $conversions,
    ) {
    }

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function isFound(): bool
    {
        return $this->storage->hasSettings();
    }

    public function read(): ImportedSettings
    {
        $general = new Config($this->storage->config('GeneralConfig'));
        $dialog = new Config($this->storage->config('DialogSettingsConfig'));
        $style = new Config($this->storage->config('DialogStyleConfig'));
        $texts = new Config($this->storage->config('DialogLocalization'));
        $widget = new Config($this->storage->config('WidgetConfig'));
        $blocker = new Config($this->storage->config('ContentBlockerConfig'));

        return new ImportedSettings(
            \array_filter(
                [
                    PluginSetting::ACTIVE => $this->isOn($general, 'borlabsCookieStatus')
                        && $this->isOn($dialog, 'showDialog') ? '1' : '',
                    ConsentSetting::TEXT => \wp_kses_post($this->text($texts, 'entranceDescription')),
                    ConsentSetting::ACCEPT_BUTTON_TEXT => $this->text($texts, 'entranceAcceptAllButton'),
                    ConsentSetting::REJECT_BUTTON_TEXT => $this->text($texts, 'entranceAcceptOnlyEssential'),
                    ConsentSetting::PREFERENCES_BUTTON_TEXT => $this->text($texts, 'entrancePreferencesButton'),
                    ConsentSetting::SAVE_BUTTON_TEXT => $this->text($texts, 'entranceSaveConsentButton'),
                    ...$this->link(BannerLink::Policy, $dialog, 'privacyPage', $this->text($texts, 'privacyLink')),
                    ...$this->link(
                        BannerLink::LegalNotice,
                        $dialog,
                        'imprintPage',
                        $this->text($texts, 'imprintLink')
                    ),
                    ConsentSetting::EXCLUDED_PAGES => $this->excludedPages($dialog),
                    ...$this->style($dialog, $style),
                    ...$this->behaviour($general, $widget),
                    BlockingSetting::ALLOWED_PATTERNS => \array_values(\array_filter(
                        \array_map($this->conversions->text(...), (array) $blocker->get('excludedHostnames', [])),
                        static fn(string $host): bool => $host !== ''
                    )),
                    ...$this->services->settings(),
                ],
                static fn(mixed $value): bool => $value !== '' && $value !== []
            ),
            $this->services->vendorsInUse(),
            [
                ...$this->services->notes(),
                \__(
                    'The geo-targeting, the IAB TCF and the translations are not imported.',
                    'italy-cookie-choices'
                ),
                \__(
                    'Deactivate Borlabs Cookie after the import, or the site shows two banners. Its settings stay.',
                    'italy-cookie-choices'
                ),
            ]
        );
    }

    /**
     * A link of the dialog: a page of the site, or an address written by hand.
     *
     * @param Data $dialog
     * @return array<string, string>
     */
    private function link(BannerLink $link, Config $dialog, string $prefix, string $text): array
    {
        $page = $this->conversions->page((int) $this->text($dialog, $prefix . 'Id'));
        $url = $page['url'] ?? $this->text($dialog, $prefix . 'CustomUrl');
        if ($url === '') {
            return [];
        }

        return [
            $link->pageSetting() => $page['slug'] ?? '',
            $link->urlSetting() => \esc_url_raw($url),
            $link->textSetting() => $text !== '' ? $text : $page['title'] ?? '',
        ];
    }

    /**
     * The pages where the dialog does not open by itself, their addresses as pages of the site.
     *
     * @param Data $dialog
     */
    private function excludedPages(Config $dialog): string
    {
        $slugs = [];
        foreach ((array) $dialog->get('hideDialogOnPages', []) as $url) {
            $page = $this->conversions->pageOfUrl($this->conversions->text($url));
            if ($page !== null) {
                $slugs[] = $page['slug'];
            }
        }

        return \implode(', ', $slugs);
    }

    /**
     * @param Data $dialog
     * @param Data $style
     * @return array<string, string>
     */
    private function style(Config $dialog, Config $style): array
    {
        $css = $this->text($style, 'customCss');

        return [
            ConsentSetting::POSITION => $this->position(
                $this->text($dialog, 'layout'),
                $this->text($dialog, 'position')
            ),
            ConsentSetting::LOGO => $this->isOn($dialog, 'showLogo')
                ? $this->conversions->attachmentOfUrl($this->text($dialog, 'logo'))
                : '',
            ConsentSetting::BACKGROUND_COLOR => $this->conversions->color($style->get('dialogBackgroundColor')),
            ConsentSetting::TEXT_COLOR => $this->conversions->color($style->get('dialogTextColor')),
            ConsentSetting::CUSTOM_CSS => $css !== '' ? $this->conversions->commentedCss(self::NAME, $css) : '',
        ];
    }

    /**
     * The bars at the top or at the bottom, the boxes in a bottom corner, the others in the middle.
     */
    private function position(string $layout, string $position): string
    {
        if (\str_starts_with($layout, 'bar')) {
            return self::BAR[\explode('-', $position)[0]] ?? '';
        }

        return $layout === '' ? '' : self::BOX[$position] ?? self::MODAL;
    }

    /**
     * @param Data $general
     * @param Data $widget
     * @return array<string, string>
     */
    private function behaviour(Config $general, Config $widget): array
    {
        $lifetime = $general->get('cookieLifetime');
        $hasWidget = $this->isOn($widget, 'show');

        return [
            ConsentSetting::CONSENT_DURATION => \is_numeric($lifetime)
                ? $this->conversions->consentDuration((int) $lifetime)
                : '',
            ConsentSetting::COOKIE_DOMAIN => $this->isOn($general, 'automaticCookieDomainAndPath')
                ? ''
                : $this->conversions->cookieDomain($this->text($general, 'cookieDomain')),
            ConsentSetting::RELOAD => $this->isOn($general, 'reloadAfterOptIn') ? '1' : '',
            ConsentSetting::FLOATING_BUTTON => $hasWidget ? '1' : '',
            ConsentSetting::FLOATING_BUTTON_POSITION => $hasWidget
                ? (\str_ends_with($this->text($widget, 'position'), 'left') ? 'left' : 'right')
                : '',
        ];
    }

    /**
     * @param Data $data
     */
    private function text(Config $data, string $key): string
    {
        return $this->conversions->text($data->get($key));
    }

    /**
     * @param Data $data
     */
    private function isOn(Config $data, string $key): bool
    {
        return $this->conversions->isOn($data->get($key));
    }
}
