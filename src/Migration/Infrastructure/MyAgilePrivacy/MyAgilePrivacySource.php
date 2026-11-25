<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\MyAgilePrivacy;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Blocking\Domain\CustomScript;
use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Migration\Application\Conversions;
use ItalyCookieChoices\Migration\Application\TagIds;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Settings\PluginSetting;
use ItalyStrap\Config\Config;

/**
 * Imports the settings of My Agile Privacy: the banner, the texts the site owner changed, the cookie policy link,
 * the layout and colors, the button to change the consent, the consent signals and the services in use.
 *
 * A service of a common vendor turns it on, the ids of the Google and Facebook tags in its code become the tags
 * the plugin prints, the code of the others a script after the consent. My Agile Privacy has no categories,
 * only necessary services or not: a service of a common vendor goes in the category of the vendor, the others
 * in marketing. Only its settings in the database are read, nothing of the plugin is used.
 *
 * @phpstan-import-type Service from MyAgilePrivacyStorage
 * @phpstan-type Data Config<array-key, mixed>
 * @phpstan-type Services array{
 *     inUse: list<string>,
 *     tags: array<string, string>,
 *     scripts: list<array<string, string>>,
 *     unknown: list<string>,
 *     necessary: list<string>
 * }
 */
final readonly class MyAgilePrivacySource implements MigrationSource
{
    public const ID = 'myagileprivacy';
    public const NAME = 'My Agile Privacy';

    /** The services of My Agile Privacy whose key, with dashes, is not the id of the common vendor. */
    private const SERVICES = [
        'my_agile_pixel_ga' => VendorId::GOOGLE_ANALYTICS,
        'facebook_remarketing' => VendorId::FACEBOOK,
        'my_agile_pixel_fbq' => VendorId::FACEBOOK,
        'tik_tok' => VendorId::TIKTOK,
        'my_agile_pixel_tiktok' => VendorId::TIKTOK,
        'microsoft_ads' => VendorId::MICROSOFT_ADVERTISING,
    ];

    /** The texts of the banner, by their key in the translations of My Agile Privacy. */
    private const TEXTS = [
        'notify_message_v2' => ConsentSetting::TEXT,
        'accept' => ConsentSetting::ACCEPT_BUTTON_TEXT,
        'refuse' => ConsentSetting::REJECT_BUTTON_TEXT,
        'customize' => ConsentSetting::PREFERENCES_BUTTON_TEXT,
    ];

    /** The settings of My Agile Privacy that turn a feature on, as the settings of the plugin. */
    private const SIGNALS = [
        'enable_cmode_v2' => IntegrationSetting::CONSENT_MODE,
        'enable_microsoft_cmode' => IntegrationSetting::UET_CONSENT_MODE,
        'enable_clarity_cmode' => IntegrationSetting::CLARITY_CONSENT_MODE,
        'enforce_youtube_privacy' => IntegrationSetting::YOUTUBE_NOCOOKIE,
    ];

    private const BAR = ['Top' => '1', 'Bottom' => '3'];
    private const BOX = ['Left' => '4', 'Right' => '5'];
    private const MODAL = '2';
    private const CENTER = 'Center';

    /** The sizes of the banner as wide as the page: My Agile Privacy centers them. */
    private const WIDE_SIZES = ['sizeWideBranded', 'sizeWide'];
    private const DEFAULT_SIZE = 'sizeWideBranded';

    private const LEFT = 'left';
    private const RIGHT = 'right';

    public function __construct(
        private MyAgilePrivacyStorage $storage,
        private Conversions $conversions,
        private VendorPresets $presets,
        private TagIds $tagIds,
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
        $services = $this->services();
        $css = $this->conversions->commentedCss(self::NAME, $this->text($settings, 'custom_css'));

        return new ImportedSettings(
            \array_filter(
                [
                    PluginSetting::ACTIVE => $this->isOn($settings, 'is_on') ? '1' : '',
                    ...$this->texts($settings),
                    ...$this->policy($settings),
                    ConsentSetting::POSITION => $this->position($settings),
                    ConsentSetting::BACKGROUND_COLOR => $this->conversions->color($settings->get('background')),
                    ConsentSetting::TEXT_COLOR => $this->conversions->color($settings->get('text')),
                    ...$this->floatingButton($settings),
                    ...$this->signals($settings),
                    ConsentSetting::CUSTOM_CSS => $css,
                    BlockingSetting::CUSTOM_SCRIPTS => $services['scripts'],
                    ...$services['tags'],
                ],
                static fn(mixed $value): bool => $value !== '' && $value !== []
            ),
            \array_values(\array_unique($services['inUse'])),
            \array_values(\array_filter([
                $services['unknown'] === [] ? '' : \sprintf(
                    /* translators: %s: the names of the services, separated by commas. */
                    \__(
                        'These services are not common vendors: add the addresses of their scripts as custom '
                            . 'vendors, their code runs after the consent to marketing: %s.',
                        'italy-cookie-choices'
                    ),
                    \implode(', ', $services['unknown'])
                ),
                $services['necessary'] === [] ? '' : \sprintf(
                    /* translators: %s: the names of the services, separated by commas. */
                    \__(
                        'These necessary services are not imported, add their cookies to the cookie table: %s.',
                        'italy-cookie-choices'
                    ),
                    \implode(', ', $services['necessary'])
                ),
                $css === '' ? '' : $this->conversions->customCssNote(),
                \__(
                    'The title, the texts of the preferences, the policies, the geolocation and the IAB TCF '
                        . 'are not imported.',
                    'italy-cookie-choices'
                ),
                \__(
                    'Deactivate My Agile Privacy after the import, or the site shows two banners. Its settings stay.',
                    'italy-cookie-choices'
                ),
            ]))
        );
    }

    /**
     * The texts the site owner changed, in the language of the banner: the default ones are not imported.
     *
     * @param Data $settings
     * @return array<string, string>
     */
    private function texts(Config $settings): array
    {
        $language = $this->text($settings, 'default_locale');
        $translations = \json_decode($this->text($settings, 'fixed_translations_encoded'), true);
        $translations = new Config(\is_array($translations) ? $translations : []);
        $texts = new Config((array) $translations->get($language !== '' ? $language : \get_locale(), []));

        $result = [];
        foreach (self::TEXTS as $key => $setting) {
            $result[$setting] = $this->text($texts, $key);
        }

        $result[ConsentSetting::TEXT] = \wp_kses_post($result[ConsentSetting::TEXT]);

        return $result;
    }

    /**
     * The cookie policy link: an address or a page of the site.
     *
     * @param Data $settings
     * @return array<string, string>
     */
    private function policy(Config $settings): array
    {
        $link = BannerLink::Policy;
        $url = $this->text($settings, 'cookie_policy_url');
        if ($this->isOn($settings, 'is_cookie_policy_url')) {
            return $url === '' ? [] : [$link->urlSetting() => \esc_url_raw($url)];
        }

        $page = $this->conversions->page((int) $this->text($settings, 'cookie_policy_page'));
        if ($page === null) {
            return [];
        }

        return [
            $link->pageSetting() => $page['slug'],
            $link->urlSetting() => \esc_url_raw($page['url']),
            $link->textSetting() => $page['title'],
        ];
    }

    /**
     * The banner in the middle, the box on its side, the bar at the top or at the bottom.
     * The banners of the first versions have no position, only the bottom or the top.
     *
     * @param Data $settings
     */
    private function position(Config $settings): string
    {
        $vertical = $this->text($settings, 'cookie_banner_vertical_position');
        if ($vertical === '') {
            $isTop = $settings->has('is_bottom') && !$this->isOn($settings, 'is_bottom');

            return $isTop ? self::BAR['Top'] : self::BAR['Bottom'];
        }

        if ($vertical === self::CENTER) {
            return self::MODAL;
        }

        $size = $this->text($settings, 'cookie_banner_size');
        $isWide = \in_array($size !== '' ? $size : self::DEFAULT_SIZE, self::WIDE_SIZES, true);
        $horizontal = $isWide ? self::CENTER : $this->text($settings, 'cookie_banner_horizontal_position');

        return self::BOX[$horizontal] ?? self::BAR[$vertical] ?? self::BAR['Bottom'];
    }

    /**
     * @param Data $settings
     * @return array<string, string>
     */
    private function floatingButton(Config $settings): array
    {
        if (!$this->isOn($settings, 'showagain_tab')) {
            return [];
        }

        $isLeft = $this->text($settings, 'notify_position_horizontal') === self::LEFT;

        return [
            ConsentSetting::FLOATING_BUTTON => '1',
            ConsentSetting::FLOATING_BUTTON_POSITION => $isLeft ? self::LEFT : self::RIGHT,
        ];
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
     * The services in use: the necessary ones are not blocked, the others are blocked until the consent.
     *
     * @return Services
     */
    private function services(): array
    {
        $result = ['inUse' => [], 'tags' => [], 'scripts' => [], 'unknown' => [], 'necessary' => []];
        foreach ($this->storage->services() as $service) {
            if ($service['isNecessary']) {
                $result['necessary'][] = $service['name'];
                continue;
            }

            $vendor = $this->vendorOf($service);
            $result = $vendor === null ? $this->withUnknown($result, $service) : $this->withVendor(
                $result,
                $service,
                $vendor
            );
        }

        return $result;
    }

    /**
     * @param Services $result
     * @param Service $service
     * @return Services
     */
    private function withVendor(array $result, array $service, Vendor $vendor): array
    {
        $result['inUse'][] = $vendor->id();
        $tags = $this->tagIds->of($service['code']);
        $result['tags'] = [...$result['tags'], ...$tags];
        if ($this->tagIds->isPrinted($vendor->id()) && $tags !== []) {
            return $result;
        }

        $result['scripts'] = $this->withScript($result['scripts'], $service, $vendor->category());

        return $result;
    }

    /**
     * @param Services $result
     * @param Service $service
     * @return Services
     */
    private function withUnknown(array $result, array $service): array
    {
        $result['unknown'][] = $service['name'];
        $result['scripts'] = $this->withScript($result['scripts'], $service, Category::MARKETING);

        return $result;
    }

    /**
     * @param list<array<string, string>> $scripts
     * @param Service $service
     * @return list<array<string, string>>
     */
    private function withScript(array $scripts, array $service, string $category): array
    {
        if ($service['code'] === '') {
            return $scripts;
        }

        $scripts[] = [
            'name' => $service['name'],
            'category' => $category,
            'position' => CustomScript::BODY,
            'code' => $service['code'],
        ];

        return $scripts;
    }

    /**
     * The common vendor of a service, by its key or its name.
     *
     * @param Service $service
     */
    private function vendorOf(array $service): ?Vendor
    {
        $id = self::SERVICES[$service['key']] ?? \str_replace('_', '-', $service['key']);
        foreach ($this->presets->all() as $vendor) {
            $isSame = $vendor->id() === $id
                || \strcasecmp($this->presets->name($vendor->id()), $service['name']) === 0;
            if ($isSame) {
                return $vendor;
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

    /**
     * @param Data $data
     */
    private function isOn(Config $data, string $key): bool
    {
        return $this->conversions->isOn($data->get($key));
    }
}
