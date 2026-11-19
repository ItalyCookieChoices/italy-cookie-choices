<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\GdprCookieCompliance;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Blocking\Domain\CustomScript;
use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Migration\Application\Conversions;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Settings\PluginSetting;
use ItalyStrap\Config\Config;

/**
 * Imports the settings of GDPR Cookie Compliance, by Moove Agency, the free version.
 *
 * Its tabs are the necessary cookies, the third party cookies (statistics) and the additional cookies
 * (marketing), each with scripts run after its consent. GDPR Cookie Compliance is GPL, the import reads its
 * settings in the database, no code of it is used.
 *
 * @phpstan-type Data Config<array-key, mixed>
 */
final readonly class GdprCookieComplianceSource implements MigrationSource
{
    public const ID = 'gdpr-cookie-compliance';
    public const NAME = 'GDPR Cookie Compliance';

    private const PREFIX = 'moove_gdpr_';
    private const HIDDEN = 'hidden';

    private const POSITIONS = ['top' => '1', 'bottom' => '3', 'bottom_left' => '4', 'bottom_right' => '5'];

    /**
     * The color schemes: the background and the text.
     */
    private const SCHEMES = ['1' => ['#202020', '#ffffff'], '2' => ['#ffffff', '#202020']];

    /**
     * The tabs: their category, the setting that turns them on, the keys of their title and of their text.
     */
    private const TABS = [
        'strictly' => [
            Category::NECESSARY,
            '',
            'strictly_necessary_cookies_tab_title',
            'strict_necessary_cookies_tab_content',
        ],
        'third_party' => [
            Category::STATISTICS,
            'third_party_cookies_enable',
            'performance_cookies_tab_title',
            'performance_cookies_tab_content',
        ],
        'advanced_cookies' => [
            Category::MARKETING,
            'advanced_cookies_enable',
            'advanced_cookies_tab_title',
            'advanced_cookies_tab_content',
        ],
    ];

    /**
     * The positions of the scripts of a tab.
     */
    private const SCRIPT_POSITIONS = [
        'header' => CustomScript::HEAD,
        'body' => CustomScript::BODY,
        'footer' => CustomScript::BODY,
    ];

    /**
     * The placeholders of the link to the settings in the text of the banner.
     */
    private const PLACEHOLDERS = '/[\[{]\/?setting[\]}]/';

    public function __construct(
        private GdprCookieComplianceStorage $storage,
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
        $settings = new Config($this->storage->settings());
        $integrations = new Config($this->integrations($settings));

        return new ImportedSettings(
            \array_filter(
                [
                    ...$this->banner($settings),
                    ConsentSetting::CATEGORIES => $this->categories($settings),
                    BlockingSetting::CUSTOM_SCRIPTS => $this->scripts($settings),
                    ...$this->tags($integrations),
                ],
                static fn(mixed $value): bool => $value !== '' && $value !== []
            ),
            $integrations->get('gadc') !== null ? [VendorId::GOOGLE_ADS] : [],
            $this->notes($settings, $integrations)
        );
    }

    /**
     * @param Data $settings
     * @return array<string, string>
     */
    private function banner(Config $settings): array
    {
        $isHidden = $this->text($settings, 'infobar_visibility') === self::HIDDEN;
        $hasButton = $isHidden || $this->conversions->isOn($settings->get(self::PREFIX . 'floating_button_enable'));
        $expiry = $settings->get(self::PREFIX . 'consent_expiration');
        $scheme = self::SCHEMES[$this->text($settings, 'colour_scheme')] ?? ['', ''];

        return [
            PluginSetting::ACTIVE => $isHidden ? '' : '1',
            ConsentSetting::TEXT => \trim((string) \preg_replace(
                self::PLACEHOLDERS,
                '',
                \wp_kses_post($this->localized($settings, 'info_bar_content'))
            )),
            ConsentSetting::ACCEPT_BUTTON_TEXT => $this->localized($settings, 'infobar_accept_button_label'),
            ConsentSetting::REJECT_BUTTON_TEXT => $this->localized($settings, 'infobar_reject_button_label'),
            ConsentSetting::PREFERENCES_BUTTON_TEXT => $this->localized($settings, 'infobar_settings_button_label'),
            ConsentSetting::SAVE_BUTTON_TEXT => $this->localized($settings, 'modal_save_button_label'),
            ConsentSetting::POSITION => self::POSITIONS[$this->text($settings, 'infobar_position')] ?? '',
            ConsentSetting::BACKGROUND_COLOR => $scheme[0],
            ConsentSetting::TEXT_COLOR => $scheme[1],
            ConsentSetting::LOGO => \ctype_digit($this->text($settings, 'company_logo_id'))
                ? $this->text($settings, 'company_logo_id')
                : '',
            ConsentSetting::CONSENT_DURATION => \is_numeric($expiry)
                ? $this->conversions->consentDuration(\max(1, (int) $expiry))
                : '',
            ConsentSetting::RELOAD => $this->conversions->isOn($settings->get('gdpr_force_reload')) ? '1' : '',
            ConsentSetting::FLOATING_BUTTON => $hasButton ? '1' : '',
            ConsentSetting::FLOATING_BUTTON_POSITION => $hasButton ? 'left' : '',
            ConsentSetting::FLOATING_BUTTON_LABEL => $hasButton
                ? $this->localized($settings, 'floating_button_label')
                : '',
        ];
    }

    /**
     * The texts of the tabs turned on.
     *
     * @param Data $settings
     * @return array<string, array{label: string, description: string}>
     */
    private function categories(Config $settings): array
    {
        $categories = [];
        foreach (self::TABS as [$category, $enable, $title, $content]) {
            if ($enable !== '' && !$this->conversions->isOn($settings->get(self::PREFIX . $enable))) {
                continue;
            }

            $texts = [
                'label' => $this->localized($settings, $title),
                'description' => \trim(\wp_strip_all_tags($this->localized($settings, $content))),
            ];
            if ($texts !== ['label' => '', 'description' => '']) {
                $categories[$category] = $texts;
            }
        }

        return $categories;
    }

    /**
     * The scripts of each tab, run after its consent.
     *
     * @param Data $settings
     * @return list<array{name: string, category: string, position: string, code: string}>
     */
    private function scripts(Config $settings): array
    {
        $scripts = [];
        foreach (self::TABS as $tab => [$category, , $title]) {
            foreach (self::SCRIPT_POSITIONS as $place => $position) {
                $code = $this->text($settings, $tab . '_' . $place . '_scripts');
                if ($code === '') {
                    continue;
                }

                $scripts[] = [
                    'name' => \sprintf($this->nameFormat($place), $this->label($settings, $title, $category)),
                    'category' => $category,
                    'position' => $position,
                    'code' => $code,
                ];
            }
        }

        return $scripts;
    }

    /**
     * The name of a script of a tab in a place of the page.
     */
    private function nameFormat(string $place): string
    {
        return match ($place) {
            /* translators: %s: the name of the tab of the other plugin. */
            'header' => \__('%s (head)', 'italy-cookie-choices'),
            /* translators: %s: the name of the tab of the other plugin. */
            'footer' => \__('%s (footer)', 'italy-cookie-choices'),
            /* translators: %s: the name of the tab of the other plugin. */
            default => \__('%s (body)', 'italy-cookie-choices'),
        };
    }

    /**
     * The name of a tab, its title or its category.
     *
     * @param Data $settings
     */
    private function label(Config $settings, string $title, string $category): string
    {
        $label = $this->localized($settings, $title);

        return $label !== '' ? $label : \ucfirst($category);
    }

    /**
     * The integrations of the tags, a JSON of ids and categories.
     *
     * @param Data $settings
     * @return array<array-key, mixed>
     */
    private function integrations(Config $settings): array
    {
        $json = $settings->get('gdin_values');
        $integrations = \is_string($json) ? \json_decode($json, true) : $json;

        return \is_array($integrations) ? $integrations : [];
    }

    /**
     * The Google and Facebook tags, printed by Cookie Choices in its categories.
     *
     * @param Data $integrations
     * @return array<string, string>
     */
    private function tags(Config $integrations): array
    {
        $tagManager = $this->tagId($integrations, 'gtm_id', 'GTM-');

        return [
            IntegrationSetting::GA_MEASUREMENT_ID => $this->tagId($integrations, 'ga4_id', 'G-'),
            IntegrationSetting::GTM_CONTAINER_ID => $tagManager !== ''
                ? $tagManager
                : $this->tagId($integrations, 'gtmc2_id', 'GTM-'),
            IntegrationSetting::FACEBOOK_PIXEL_ID => $this->pixelId($integrations),
            IntegrationSetting::CONSENT_MODE => $integrations->get('gtmc2') !== null ? '1' : '',
        ];
    }

    /**
     * @param Data $integrations
     */
    private function pixelId(Config $integrations): string
    {
        $id = $this->conversions->text($integrations->get('fbp_id'));

        return \ctype_digit($id) ? $id : '';
    }

    /**
     * @param Data $integrations
     */
    private function tagId(Config $integrations, string $key, string $prefix): string
    {
        $id = \strtoupper($this->conversions->text($integrations->get($key)));

        return \str_starts_with($id, $prefix) ? $id : '';
    }

    /**
     * @param Data $settings
     * @param Data $integrations
     * @return list<string>
     */
    private function notes(Config $settings, Config $integrations): array
    {
        $notes = [];
        if ($this->scripts($settings) !== []) {
            $notes[] = \__(
                'The scripts of the tabs are scripts after the consent: check them in Integrations.',
                'italy-cookie-choices'
            );
        }

        $googleAds = $this->conversions->text($integrations->get('gadc_id'));
        if ($googleAds !== '') {
            $notes[] = \sprintf(
                /* translators: %s: the id of the Google Ads tag, like AW-123. */
                \__(
                    'The Google Ads tag %s is not imported: add it to the theme, '
                    . 'Google Ads is blocked until the marketing consent.',
                    'italy-cookie-choices'
                ),
                $googleAds
            );
        }

        return [
            ...$notes,
            \__(
                'Translations are not imported: write the texts again in WPML or Polylang.',
                'italy-cookie-choices'
            ),
            \__(
                'Deactivate GDPR Cookie Compliance after the import, or the site shows two banners. Its settings stay.',
                'italy-cookie-choices'
            ),
        ];
    }

    /**
     * A text in the default language of WPML or Polylang, or the text without a language.
     *
     * @param Data $settings
     */
    private function localized(Config $settings, string $key): string
    {
        $language = $this->storage->defaultLanguage();
        $text = $language !== '' ? $this->text($settings, $key . '_' . $language) : '';

        return $text !== '' ? $text : $this->text($settings, $key);
    }

    /**
     * @param Data $settings
     */
    private function text(Config $settings, string $key): string
    {
        return $this->conversions->text($settings->get(self::PREFIX . $key));
    }
}
