<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\WpConsent;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Cookies\Application\CookieSetting;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Migration\Application\Conversions;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Settings\PluginSetting;
use ItalyStrap\Config\Config;

/**
 * Imports the settings of WPConsent: the banner, its texts, cookie policy page, layout and colors,
 * the consent signals, the categories, and the services with their cookies.
 *
 * A service of a common vendor turns it on. WPConsent blocks the other services with its own library,
 * not with addresses: their cookies become site cookies in their category, for the cookie table.
 * Only its settings in the database are read, nothing of the plugin is used.
 *
 * @phpstan-import-type Term from \ItalyCookieChoices\Migration\Infrastructure\UnregisteredContent
 * @phpstan-type Data Config<array-key, mixed>
 */
final readonly class WpConsentSource implements MigrationSource
{
    public const ID = 'wpconsent';
    public const NAME = 'WPConsent';

    /** The categories of WPConsent, by slug. The custom ones are marketing. */
    private const CATEGORIES = [
        'essential' => Category::NECESSARY,
        'functional' => Category::PREFERENCES,
        'preferences' => Category::PREFERENCES,
        'statistics' => Category::STATISTICS,
        'marketing' => Category::MARKETING,
    ];

    /** The services of WPConsent whose slug is not the id of the common vendor. */
    private const SERVICES = [
        'facebook-pixel' => VendorId::FACEBOOK,
        'meta-pixel' => VendorId::FACEBOOK,
        'tiktok-pixel' => VendorId::TIKTOK,
        'linkedin-insight-tag' => VendorId::LINKEDIN,
        'clarity' => VendorId::MICROSOFT_CLARITY,
        'bing-ads' => VendorId::MICROSOFT_ADVERTISING,
        'adsense' => VendorId::GOOGLE_ADSENSE,
        'recaptcha' => VendorId::GOOGLE_RECAPTCHA,
    ];

    private const BAR = ['top' => '1', 'bottom' => '3'];
    private const BOX = ['left-top' => '4', 'left-bottom' => '4', 'right-top' => '5', 'right-bottom' => '5'];
    private const MODAL = '2';
    private const LONG_LAYOUT = 'long';
    private const FLOATING_LAYOUT = 'floating';

    /** The consent cookie of WPConsent, replaced by the one of the plugin. */
    private const OWN_COOKIE = 'wpconsent_preferences';

    /** The settings of WPConsent that turn a feature on, as the settings of the plugin. */
    private const SIGNALS = [
        'google_consent_mode' => IntegrationSetting::CONSENT_MODE,
        'respect_gpc' => ConsentSetting::GLOBAL_PRIVACY_CONTROL,
    ];

    public function __construct(
        private WpConsentStorage $storage,
        private Conversions $conversions,
        private VendorPresets $presets,
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
        $settings = new Config($this->storage->settings());
        $terms = $this->storage->terms();
        $categories = $this->categories($terms);
        $services = $this->services($terms, $categories);
        $cookies = $this->cookies($categories, $services);

        return new ImportedSettings(
            \array_filter(
                [
                    PluginSetting::ACTIVE => $this->isOn($settings, 'enable_consent_banner') ? '1' : '',
                    ConsentSetting::TEXT => \wp_kses_post($this->text($settings, 'banner_message')),
                    ConsentSetting::ACCEPT_BUTTON_TEXT => $this->text($settings, 'accept_button_text'),
                    ConsentSetting::REJECT_BUTTON_TEXT => $this->text($settings, 'cancel_button_text'),
                    ConsentSetting::PREFERENCES_BUTTON_TEXT => $this->text($settings, 'preferences_button_text'),
                    ConsentSetting::SAVE_BUTTON_TEXT => $this->text($settings, 'save_preferences_button_text'),
                    ...$this->policy((int) $this->text($settings, 'cookie_policy_page')),
                    ConsentSetting::POSITION => $this->position($settings),
                    ConsentSetting::LOGO => $this->conversions->attachmentOfUrl($this->text($settings, 'banner_logo')),
                    ConsentSetting::BACKGROUND_COLOR => $this->conversions->color(
                        $settings->get('banner_background_color')
                    ),
                    ConsentSetting::TEXT_COLOR => $this->conversions->color($settings->get('banner_text_color')),
                    ConsentSetting::CONSENT_DURATION => $this->duration($settings),
                    ...$this->signals($settings),
                    ConsentSetting::CATEGORIES => $categories['texts'],
                    CookieSetting::SITE_COOKIES => $cookies,
                ],
                static fn(mixed $value): bool => $value !== '' && $value !== []
            ),
            \array_values(\array_unique(\array_filter($services['vendors']))),
            \array_values(\array_filter([
                $services['unknown'] === [] ? '' : \sprintf(
                    /* translators: %s: the names of the services, separated by commas. */
                    \__(
                        'WPConsent blocks these services with its own list: their cookies are site cookies now, '
                            . 'add the addresses of their scripts as custom vendors: %s.',
                        'italy-cookie-choices'
                    ),
                    \implode(', ', $services['unknown'])
                ),
                \__(
                    'The texts of the preferences, the geolocation, the IAB TCF and the legal documents '
                        . 'are not imported.',
                    'italy-cookie-choices'
                ),
                \__(
                    'Deactivate WPConsent after the import, or the site shows two banners. Its settings stay.',
                    'italy-cookie-choices'
                ),
            ]))
        );
    }

    /**
     * The categories with no parent, as the categories of the plugin, and their texts: the first one wins.
     *
     * @param list<Term> $terms
     * @return array{ids: array<int, string>, texts: array<string, array{label: string, description: string}>}
     */
    private function categories(array $terms): array
    {
        $ids = [];
        $texts = [];
        foreach ($terms as $term) {
            if ($term['parent'] !== 0) {
                continue;
            }

            $category = self::CATEGORIES[$term['slug']] ?? Category::MARKETING;
            $ids[$term['id']] = $category;
            $texts[$category] ??= [
                'label' => $term['name'],
                'description' => \wp_strip_all_tags($term['description']),
            ];
        }

        return ['ids' => $ids, 'texts' => $texts];
    }

    /**
     * The services, with a parent category: the common vendor of each one, null for the others.
     *
     * @param list<Term> $terms
     * @param array{ids: array<int, string>, texts: array<string, mixed>} $categories
     * @return array{vendors: array<int, string|null>, category: array<int, string>, unknown: list<string>}
     */
    private function services(array $terms, array $categories): array
    {
        $result = ['vendors' => [], 'category' => [], 'unknown' => []];
        foreach ($terms as $term) {
            if ($term['parent'] === 0) {
                continue;
            }

            $vendor = $this->vendorOf($term);
            $result['vendors'][$term['id']] = $vendor;
            $result['category'][$term['id']] = $categories['ids'][$term['parent']] ?? Category::MARKETING;
            $isEssential = ($categories['ids'][$term['parent']] ?? '') === Category::NECESSARY;
            if ($vendor === null && !$isEssential) {
                $result['unknown'][] = $term['name'];
            }
        }

        return $result;
    }

    /**
     * The common vendor of a service, by its slug or its name.
     *
     * @param Term $service
     */
    private function vendorOf(array $service): ?string
    {
        if (\array_key_exists($service['slug'], self::SERVICES)) {
            return self::SERVICES[$service['slug']];
        }

        foreach ($this->presets->all() as $vendor) {
            $isSame = $vendor->id() === $service['slug']
                || \strcasecmp($this->presets->name($vendor->id()), $service['name']) === 0;
            if ($isSame) {
                return $vendor->id();
            }
        }

        return null;
    }

    /**
     * The cookies of the categories and of the services no common vendor covers, as site cookies.
     *
     * @param array{ids: array<int, string>, texts: array<string, mixed>} $categories
     * @param array{vendors: array<int, string|null>, category: array<int, string>, unknown: list<string>} $services
     * @return list<array{name: string, category: string, duration: string, purpose: string}>
     */
    private function cookies(array $categories, array $services): array
    {
        $cookies = [];
        foreach ($this->storage->cookies() as $cookie) {
            $name = $cookie['name'] !== '' ? $cookie['name'] : $cookie['title'];
            $term = $cookie['terms'][0] ?? 0;
            if ($name === '' || $name === self::OWN_COOKIE || ($services['vendors'][$term] ?? null) !== null) {
                continue;
            }

            $cookies[] = [
                'name' => $name,
                'category' => $services['category'][$term] ?? $categories['ids'][$term] ?? Category::MARKETING,
                'duration' => $cookie['duration'],
                'purpose' => \wp_strip_all_tags($cookie['description']),
            ];
        }

        return $cookies;
    }

    /**
     * The cookie policy page of WPConsent, as the policy link.
     *
     * @return array<string, string>
     */
    private function policy(int $id): array
    {
        $page = $this->conversions->page($id);
        if ($page === null) {
            return [];
        }

        $link = BannerLink::Policy;

        return [
            $link->pageSetting() => $page['slug'],
            $link->urlSetting() => \esc_url_raw($page['url']),
            $link->textSetting() => $page['title'],
        ];
    }

    /**
     * The long banner at the top or at the bottom, the floating one on its side, the modal in the middle.
     *
     * @param Data $settings
     */
    private function position(Config $settings): string
    {
        $layout = $this->text($settings, 'banner_layout');
        $position = $this->text($settings, 'banner_position');

        return match ($layout) {
            self::LONG_LAYOUT => self::BAR[$position] ?? self::BAR['bottom'],
            self::FLOATING_LAYOUT => self::BOX[$position] ?? self::BOX['left-bottom'],
            '' => '',
            default => self::MODAL,
        };
    }

    /**
     * @param Data $settings
     */
    private function duration(Config $settings): string
    {
        $days = $settings->get('consent_duration');

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
            $signals[$setting] = $this->isOn($settings, $key) ? '1' : '';
        }

        return $signals;
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
