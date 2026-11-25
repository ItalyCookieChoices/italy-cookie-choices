<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\BeautifulCookieConsent;

use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Migration\Application\Conversions;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Settings\PluginSetting;
use ItalyStrap\Config\Config;

/**
 * Imports the settings of Beautiful Cookie Consent: the banner, its texts and its two links, the position,
 * the colors, the button to change the consent, the consent cookie, the consent signals, the cookie types
 * and the Google Tag Manager container. Its service blocking belongs to its premium add-on and is not read.
 * Only its settings in the database are read, nothing of the plugin is used.
 *
 * @phpstan-type Data Config<array-key, mixed>
 */
final readonly class BeautifulCookieConsentSource implements MigrationSource
{
    public const ID = 'beautiful-cookie-consent';
    public const NAME = 'Beautiful Cookie Consent';

    private const POSITIONS = [
        'top' => '1',
        'centered' => '2',
        'bottom' => '3',
        'top-left' => '4',
        'bottom-left' => '4',
        'top-right' => '5',
        'bottom-right' => '5',
    ];

    /** The banner that only informs: its button is the one to dismiss it. */
    private const INFO_TYPE = 'info';

    /** The cookie types of Beautiful Cookie Consent, by their suffix. The custom ones are marketing. */
    private const CATEGORIES = [
        'tech' => Category::NECESSARY,
        'technical' => Category::NECESSARY,
        'necessary' => Category::NECESSARY,
        'essential' => Category::NECESSARY,
        'preferences' => Category::PREFERENCES,
        'functional' => Category::PREFERENCES,
        'comfort' => Category::PREFERENCES,
        'statistics' => Category::STATISTICS,
        'analytics' => Category::STATISTICS,
        'marketing' => Category::MARKETING,
    ];

    /** The settings of the banner that turn a feature on, as the settings of the plugin. */
    private const SIGNALS = [
        'activateConsentMode' => IntegrationSetting::CONSENT_MODE,
        'microsoftClarityEnabled' => IntegrationSetting::CLARITY_CONSENT_MODE,
    ];

    /** The two links of the banner: the first is the policy, the second, often the imprint, the legal notice. */
    private const LINKS = [
        'link' => ['href', BannerLink::Policy],
        'linksecond' => ['hrefsecond', BannerLink::LegalNotice],
    ];

    private const LEFT = 'left';
    private const RIGHT = 'right';

    public function __construct(
        private BeautifulCookieConsentStorage $storage,
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
        $banner = new Config($this->storage->banner());
        $tagManager = $this->conversions->text($this->storage->option('gtm_id'));
        $isInfo = $this->text($banner, 'type') === self::INFO_TYPE;

        return new ImportedSettings(
            \array_filter(
                [
                    PluginSetting::ACTIVE => $this->conversions->isOn($this->storage->option('activate_banner'))
                        ? '1'
                        : '',
                    ConsentSetting::TEXT => \wp_kses_post($this->text($banner, 'content.message')),
                    ConsentSetting::ACCEPT_BUTTON_TEXT => $this->text(
                        $banner,
                        $isInfo ? 'content.dismiss' : 'content.allow'
                    ),
                    ConsentSetting::REJECT_BUTTON_TEXT => $isInfo ? '' : $this->text($banner, 'content.deny'),
                    ConsentSetting::SAVE_BUTTON_TEXT => $isInfo ? '' : $this->text($banner, 'content.savesettings'),
                    ...$this->links($banner),
                    ConsentSetting::POSITION => self::POSITIONS[$this->text($banner, 'position')] ?? '',
                    ConsentSetting::BACKGROUND_COLOR => $this->conversions->color(
                        $banner->get('palette.popup.background')
                    ),
                    ConsentSetting::TEXT_COLOR => $this->conversions->color($banner->get('palette.popup.text')),
                    ...$this->floatingButton($banner),
                    ConsentSetting::CONSENT_DURATION => $this->duration($banner),
                    ConsentSetting::COOKIE_DOMAIN => $this->conversions->cookieDomain(
                        \ltrim($this->text($banner, 'cookie.domain'), '.')
                    ),
                    ...$this->signals($banner),
                    IntegrationSetting::UET_CONSENT_MODE => $this->conversions->isOn(
                        $this->storage->option('microsoftUETconsentMode')
                    ) ? '1' : '',
                    IntegrationSetting::GTM_CONTAINER_ID => $tagManager,
                    ConsentSetting::CATEGORIES => $this->categories($banner),
                ],
                static fn(mixed $value): bool => $value !== '' && $value !== []
            ),
            $tagManager === '' ? [] : [VendorId::GOOGLE_TAG_MANAGER],
            [
                \__(
                    'The theme, the descriptions of the cookie types, the statistics and the service blocking '
                        . 'of the premium add-on are not imported.',
                    'italy-cookie-choices'
                ),
                \__(
                    'Deactivate Beautiful Cookie Consent after the import, or the site shows two banners. '
                        . 'Its settings stay.',
                    'italy-cookie-choices'
                ),
            ]
        );
    }

    /**
     * The links of the banner, with a page of the site when the address is one.
     *
     * @param Data $banner
     * @return array<string, string>
     */
    private function links(Config $banner): array
    {
        $links = [];
        foreach (self::LINKS as $textKey => [$urlKey, $link]) {
            $url = $this->text($banner, 'content.' . $urlKey);
            if ($url === '') {
                continue;
            }

            $page = $this->conversions->pageOfUrl($url);
            $links[$link->pageSetting()] = $page['slug'] ?? '';
            $links[$link->urlSetting()] = \esc_url_raw($url);
            $links[$link->textSetting()] = $this->text($banner, 'content.' . $textKey);
        }

        return $links;
    }

    /**
     * The button to change the consent, on by default like in Beautiful Cookie Consent.
     *
     * @param Data $banner
     * @return array<string, string>
     */
    private function floatingButton(Config $banner): array
    {
        if (!$this->conversions->isOn($banner->get('revokable', true))) {
            return [];
        }

        $isLeft = \str_ends_with($this->text($banner, 'positionRevokeButton'), self::LEFT);

        return [
            ConsentSetting::FLOATING_BUTTON => '1',
            ConsentSetting::FLOATING_BUTTON_POSITION => $isLeft ? self::LEFT : self::RIGHT,
            ConsentSetting::FLOATING_BUTTON_LABEL => $this->text($banner, 'content.policy'),
        ];
    }

    /**
     * @param Data $banner
     */
    private function duration(Config $banner): string
    {
        $days = $banner->get('cookie.expiryDays');

        return \is_numeric($days) ? $this->conversions->consentDuration((int) $days) : '';
    }

    /**
     * @param Data $banner
     * @return array<string, string>
     */
    private function signals(Config $banner): array
    {
        $signals = [];
        foreach (self::SIGNALS as $key => $setting) {
            $signals[$setting] = $this->conversions->isOn($banner->get($key)) ? '1' : '';
        }

        return $signals;
    }

    /**
     * The labels of the cookie types, as the categories of the plugin: the first one wins.
     *
     * @param Data $banner
     * @return array<string, array{label: string}>
     */
    private function categories(Config $banner): array
    {
        $types = $banner->get('cookietypes', []);
        $categories = [];
        foreach (\is_array($types) ? $types : [] as $type) {
            $type = new Config(\is_array($type) ? $type : []);
            $label = $this->text($type, 'label');
            if ($label === '') {
                continue;
            }

            $category = self::CATEGORIES[\strtolower($this->text($type, 'cookie_suffix'))] ?? Category::MARKETING;
            $categories[$category] ??= ['label' => $label];
        }

        return $categories;
    }

    /**
     * @param Data $data
     */
    private function text(Config $data, string $key): string
    {
        return $this->conversions->text($data->get($key));
    }
}
