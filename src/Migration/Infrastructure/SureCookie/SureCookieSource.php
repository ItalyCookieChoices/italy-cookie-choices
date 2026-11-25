<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\SureCookie;

use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Migration\Application\Conversions;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Settings\PluginSetting;
use ItalyStrap\Config\Config;

/**
 * Imports the settings of SureCookie: the banner, its texts, the cookie policy and legal notice pages, the layout,
 * the logo, the duration of the consent, the button to change it, Google Consent Mode, the custom CSS,
 * the categories, the services, the cookies and the blocking rules.
 * Only its settings in the database are read, nothing of the plugin is used.
 *
 * @phpstan-type Data Config<array-key, mixed>
 */
final readonly class SureCookieSource implements MigrationSource
{
    public const ID = 'surecookie';
    public const NAME = 'SureCookie';

    private const BAR = ['top' => '1', 'bottom' => '3'];
    private const BOX = ['bottom-left' => '4', 'top-left' => '4', 'bottom-right' => '5', 'top-right' => '5'];
    private const BAR_TYPE = 'banner';

    /** The settings of SureCookie that turn a feature on, as the settings of the plugin. */
    private const SIGNALS = [
        'gcm_enabled' => IntegrationSetting::CONSENT_MODE,
        'floating_widget_enabled' => ConsentSetting::FLOATING_BUTTON,
    ];

    /** The pages of the links of SureCookie, as the links of the banner. */
    private const LINKS = [
        'cookie_policy_page_id' => BannerLink::Policy,
        'legal_notice_page_id' => BannerLink::LegalNotice,
    ];

    public function __construct(
        private SureCookieStorage $storage,
        private SureCookieServices $services,
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
        $stored = $this->storage->settings();
        $settings = new Config($stored);
        $services = $this->services->read($stored);
        $css = $this->conversions->commentedCss(self::NAME, $this->text($settings, 'custom_css'));

        return new ImportedSettings(
            \array_filter(
                [
                    // SureCookie shows its banner unless it is turned off.
                    PluginSetting::ACTIVE => $this->isOff($settings, 'banner_enabled') ? '' : '1',
                    ConsentSetting::TEXT => \wp_kses_post($this->text($settings, 'message_description')),
                    ConsentSetting::ACCEPT_BUTTON_TEXT => $this->text($settings, 'accept_all_btn_text'),
                    ConsentSetting::REJECT_BUTTON_TEXT => $this->text($settings, 'decline_btn_text'),
                    ConsentSetting::PREFERENCES_BUTTON_TEXT => $this->text($settings, 'preferences_btn_text'),
                    ...$this->links($settings),
                    ConsentSetting::POSITION => $this->position($settings),
                    ConsentSetting::LOGO => $this->conversions->attachmentOfUrl($this->text($settings, 'banner_logo')),
                    ConsentSetting::CONSENT_DURATION => $this->duration($settings),
                    ...$this->signals($settings),
                    ConsentSetting::CUSTOM_CSS => $css,
                    ...$services['settings'],
                ],
                static fn(mixed $value): bool => $value !== '' && $value !== []
            ),
            $services['vendorsInUse'],
            \array_values(\array_filter([
                $services['unknown'] === [] ? '' : \sprintf(
                    /* translators: %s: the names of the services, separated by commas. */
                    \__(
                        'These services are not common vendors: add the addresses of their scripts as custom vendors, '
                            . 'their cookies are site cookies: %s.',
                        'italy-cookie-choices'
                    ),
                    \implode(', ', $services['unknown'])
                ),
                $css === '' ? '' : $this->conversions->customCssNote(),
                \__(
                    'The title, the colors, the texts of the preferences, the privacy policy, the geolocation '
                        . 'and the defaults of Google Consent Mode are not imported.',
                    'italy-cookie-choices'
                ),
                \__(
                    'Deactivate SureCookie after the import, or the site shows two banners. Its settings stay.',
                    'italy-cookie-choices'
                ),
            ]))
        );
    }

    /**
     * The cookie policy and the legal notice pages, as the links of the banner.
     *
     * @param Data $settings
     * @return array<string, string>
     */
    private function links(Config $settings): array
    {
        $links = [];
        foreach (self::LINKS as $key => $link) {
            $page = $this->conversions->page((int) $this->text($settings, $key));
            if ($page === null) {
                continue;
            }

            $links[$link->pageSetting()] = $page['slug'];
            $links[$link->urlSetting()] = \esc_url_raw($page['url']);
            $links[$link->textSetting()] = $page['title'];
        }

        return $links;
    }

    /**
     * The full-width banner at the top or at the bottom, the floating one on its side.
     *
     * @param Data $settings
     */
    private function position(Config $settings): string
    {
        $type = $this->text($settings, 'notice_type');
        $position = $this->text($settings, 'notice_position');
        if ($type === '') {
            return '';
        }

        return $type === self::BAR_TYPE
            ? self::BAR[$position] ?? self::BAR['bottom']
            : self::BOX[$position] ?? self::BOX['bottom-right'];
    }

    /**
     * @param Data $settings
     */
    private function duration(Config $settings): string
    {
        $days = $settings->get('consent_duration_days');

        return \is_numeric($days) ? $this->conversions->consentDuration((int) $days) : '';
    }

    /**
     * @param Data $settings
     * @return array<string, string>
     */
    private function signals(Config $settings): array
    {
        $signals = [];
        foreach (self::SIGNALS as $key => $setting) {
            $signals[$setting] = $this->conversions->isOn($settings->get($key)) ? '1' : '';
        }

        return $signals;
    }

    /**
     * Saved and turned off: SureCookie keeps only the settings that were saved.
     *
     * @param Data $data
     */
    private function isOff(Config $data, string $key): bool
    {
        return $data->has($key) && !$this->conversions->isOn($data->get($key));
    }

    /**
     * @param Data $data
     */
    private function text(Config $data, string $key): string
    {
        return $this->conversions->text($data->get($key));
    }
}
