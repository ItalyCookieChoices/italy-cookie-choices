<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\CookieAdmin;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Cookies\Application\CookieSetting;
use ItalyCookieChoices\Cookies\Application\PresetCookies;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Migration\Application\Conversions;
use ItalyCookieChoices\Migration\Application\CookieLifetime;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Settings\PluginSetting;
use ItalyStrap\Config\Config;

/**
 * Imports the settings of CookieAdmin: the banner of the law in use, its texts, link, layout and colors,
 * the consent signals, the embeds it blocks, and the cookies its scans found.
 *
 * A cookie of a common vendor turns the vendor on, the other cookies become site cookies in their category.
 * Only its settings in the database are read, nothing of the plugin is used.
 *
 * @phpstan-type Data Config<array-key, mixed>
 */
final readonly class CookieAdminSource implements MigrationSource
{
    public const ID = 'cookieadmin';
    public const NAME = 'CookieAdmin';

    private const BAR = ['top' => '1', 'bottom' => '3'];
    private const BOX = ['bottom_left' => '4', 'top_left' => '4', 'bottom_right' => '5', 'top_right' => '5'];
    private const MODAL = '2';
    private const FOOTER_LAYOUT = 'footer';
    private const POPUP_LAYOUT = 'popup';

    /** The categories of the scanned cookies, the unknown ones are not imported. */
    private const CATEGORIES = [
        'Necessary' => Category::NECESSARY,
        'Functional' => Category::PREFERENCES,
        'Analytics' => Category::STATISTICS,
        'Marketing' => Category::MARKETING,
    ];

    /** The embeds CookieAdmin blocks, as the common vendors. */
    private const EMBEDS = [
        'youtube' => VendorId::YOUTUBE,
        'vimeo' => VendorId::VIMEO,
        'soundcloud' => VendorId::SOUNDCLOUD,
        'dailymotion' => VendorId::DAILYMOTION,
        'maps' => VendorId::GOOGLE_MAPS,
    ];

    /** The general settings of CookieAdmin, as the settings of the plugin. */
    private const SIGNALS = [
        'google_consent_mode_v2' => IntegrationSetting::CONSENT_MODE,
        'cookieadmin_google_advance_consent_mode' => IntegrationSetting::CONSENT_MODE_ADVANCED,
        'clarity_consent' => IntegrationSetting::CLARITY_CONSENT_MODE,
        'respect_gpc' => ConsentSetting::GLOBAL_PRIVACY_CONTROL,
    ];

    public function __construct(
        private CookieAdminStorage $storage,
        private Conversions $conversions,
        private CookieLifetime $lifetime,
        private VendorPresets $presets,
        private PresetCookies $presetCookies,
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
        $banner = new Config($this->storage->banner());
        $settings = new Config($this->storage->settings());
        $cookies = $this->cookies();

        return new ImportedSettings(
            \array_filter(
                [
                    // CookieAdmin shows its banner while it is active.
                    PluginSetting::ACTIVE => '1',
                    ConsentSetting::TEXT => \wp_kses_post($this->text($banner, 'cookieadmin_notice')),
                    ConsentSetting::ACCEPT_BUTTON_TEXT => $this->text($banner, 'cookieadmin_accept_btn'),
                    ConsentSetting::REJECT_BUTTON_TEXT => $this->text($banner, 'cookieadmin_reject_btn'),
                    ConsentSetting::PREFERENCES_BUTTON_TEXT => $this->text($banner, 'cookieadmin_customize_btn'),
                    ConsentSetting::SAVE_BUTTON_TEXT => $this->text($banner, 'cookieadmin_save_btn'),
                    ...$this->policy($this->text($banner, 'cookieadmin_privacy_policy')),
                    ConsentSetting::POSITION => $this->position($banner),
                    ConsentSetting::BACKGROUND_COLOR => $this->conversions->color(
                        $banner->get('cookieadmin_consent_inside_bg_color')
                    ),
                    ConsentSetting::TEXT_COLOR => $this->conversions->color($banner->get('cookieadmin_notice_color')),
                    ConsentSetting::CONSENT_DURATION => $this->duration($banner),
                    ...$this->signals($settings),
                    CookieSetting::SITE_COOKIES => $cookies['site'],
                ],
                static fn(mixed $value): bool => $value !== '' && $value !== []
            ),
            \array_values(\array_unique([...$this->embeds($settings), ...$cookies['vendors']])),
            \array_values(\array_filter([
                $cookies['unknown'] === [] ? '' : \sprintf(
                    /* translators: %s: the names of the cookies, separated by commas. */
                    \__(
                        'These cookies have no category in CookieAdmin and are not imported: %s.',
                        'italy-cookie-choices'
                    ),
                    \implode(', ', $cookies['unknown'])
                ),
                \__(
                    'The titles, the texts of the preferences, the US laws and the custom icon are not imported.',
                    'italy-cookie-choices'
                ),
                \__(
                    'Deactivate CookieAdmin after the import, or the site shows two banners. Its settings stay.',
                    'italy-cookie-choices'
                ),
            ]))
        );
    }

    /**
     * The link to the privacy policy: a page of the site or an address.
     *
     * @return array<string, string>
     */
    private function policy(string $value): array
    {
        $page = \is_numeric($value) ? $this->conversions->page((int) $value) : $this->conversions->pageOfUrl($value);
        $url = $page['url'] ?? (\is_numeric($value) ? '' : $value);
        if ($url === '') {
            return [];
        }

        $link = BannerLink::Policy;

        return [
            $link->pageSetting() => $page['slug'] ?? '',
            $link->urlSetting() => \esc_url_raw($url),
            $link->textSetting() => $page['title'] ?? '',
        ];
    }

    /**
     * The bars at the top or at the bottom, the boxes on a side, the pop-up in the middle.
     *
     * @param Data $banner
     */
    private function position(Config $banner): string
    {
        $layout = $this->text($banner, 'cookieadmin_layout');
        $position = $this->text($banner, 'cookieadmin_position');
        if ($layout === self::POPUP_LAYOUT) {
            return self::MODAL;
        }

        if ($layout === self::FOOTER_LAYOUT) {
            return self::BAR[$position] ?? self::BAR['bottom'];
        }

        return $layout === '' ? '' : self::BOX[$position] ?? self::BOX['bottom_left'];
    }

    /**
     * @param Data $banner
     */
    private function duration(Config $banner): string
    {
        $days = $banner->get('cookieadmin_days');

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
     * @param Data $settings
     * @return list<string>
     */
    private function embeds(Config $settings): array
    {
        if (!$this->conversions->isOn($settings->get('content_blocking'))) {
            return [];
        }

        $embeds = [];
        foreach ((array) $settings->get('content_blocking_services', []) as $service) {
            $vendor = self::EMBEDS[$this->conversions->text($service)] ?? null;
            if ($vendor !== null) {
                $embeds[] = $vendor;
            }
        }

        return $embeds;
    }

    /**
     * The scanned cookies: the ones of a common vendor turn it on, the others are site cookies.
     *
     * @return array{
     *     site: list<array{name: string, category: string, duration: string, purpose: string}>,
     *     vendors: list<string>,
     *     unknown: list<string>
     * }
     */
    private function cookies(): array
    {
        $known = $this->presetCookieNames();
        $result = ['site' => [], 'vendors' => [], 'unknown' => []];
        foreach ($this->storage->cookies() as $cookie) {
            $vendor = $this->vendorOf($cookie['name'], $known);
            if ($vendor !== null) {
                $result['vendors'][] = $vendor;
                continue;
            }

            $category = self::CATEGORIES[$cookie['category']] ?? null;
            if ($category === null) {
                $result['unknown'][] = $cookie['name'];
                continue;
            }

            $result['site'][] = [
                'name' => $cookie['name'],
                'category' => $category,
                'duration' => $this->lifetime->text($cookie['seconds']),
                'purpose' => \wp_strip_all_tags($cookie['description']),
            ];
        }

        return $result;
    }

    /**
     * The names of the cookies of the common vendors, with a * at the end for a prefix, by vendor.
     *
     * @return array<string, list<string>>
     */
    private function presetCookieNames(): array
    {
        $names = [];
        foreach ($this->presets->all() as $vendor) {
            $names[$vendor->id()] = \array_column($this->presetCookies->of($vendor->id()), 'name');
        }

        return $names;
    }

    /**
     * @param array<string, list<string>> $known
     */
    private function vendorOf(string $name, array $known): ?string
    {
        foreach ($known as $vendor => $names) {
            foreach ($names as $pattern) {
                $matches = \str_ends_with($pattern, '*')
                    ? \str_starts_with($name, \substr($pattern, 0, -1))
                    : $pattern === $name;
                if ($matches) {
                    return $vendor;
                }
            }
        }

        return null;
    }

    /**
     * @param Data $data
     */
    private function text(Config $data, string $key): string
    {
        return $this->conversions->text($data->get($key));
    }
}
