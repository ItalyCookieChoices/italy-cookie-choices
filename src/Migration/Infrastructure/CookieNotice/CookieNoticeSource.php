<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\CookieNotice;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Blocking\Domain\CustomScript;
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
 * Imports the settings of Cookie Notice & Compliance: the banner of the plugin, or what the Compliance app
 * keeps on the site.
 *
 * Cookie Notice asks one consent for everything: the scripts it runs after the accept wait for the marketing
 * consent. Cookie Notice is GPL, the import reads its settings in the database, no code of it is used.
 *
 * @phpstan-type Data Config<array-key, mixed>
 */
final readonly class CookieNoticeSource implements MigrationSource
{
    public const ID = 'cookie-notice';
    public const NAME = 'Cookie Notice & Compliance';

    private const SHORT_NAME = 'Cookie Notice';

    /**
     * The expirations of the consent of Cookie Notice, in days.
     */
    private const DURATIONS = [
        'hour' => 1,
        'day' => 1,
        'week' => 7,
        'month' => 30,
        '3months' => 90,
        '6months' => 182,
        'year' => 365,
        'infinity' => 365,
    ];

    /**
     * The scripts run after the accept, by their position.
     */
    private const SCRIPTS = ['refuse_code_head' => CustomScript::HEAD, 'refuse_code' => CustomScript::BODY];

    private const POSITIONS = ['top' => '1', 'bottom' => '3', 'popup' => '2', 'left' => '4', 'right' => '5'];

    /**
     * The categories of the Compliance app, by id.
     */
    private const CATEGORIES = [
        1 => Category::NECESSARY,
        2 => Category::PREFERENCES,
        3 => Category::STATISTICS,
        4 => Category::MARKETING,
    ];

    public function __construct(
        private CookieNoticeStorage $storage,
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
        $options = new Config($this->storage->options());
        $settings = $this->storage->usesTheApp() ? $this->app() : $this->banner($options);

        return new ImportedSettings(
            \array_filter(
                [PluginSetting::ACTIVE => '1', ...$settings],
                static fn(mixed $value): bool => $value !== '' && $value !== []
            ),
            [],
            $this->notes($options)
        );
    }

    /**
     * The banner of the plugin.
     *
     * @param Data $options
     * @return array<string, mixed>
     */
    private function banner(Config $options): array
    {
        $hasButton = $this->isOn($options, 'revoke_cookies')
            && $this->text($options, 'revoke_cookies_opt') === 'automatic';

        return [
            ConsentSetting::TEXT => \wp_kses_post($this->text($options, 'message_text')),
            ConsentSetting::ACCEPT_BUTTON_TEXT => $this->text($options, 'accept_text'),
            ConsentSetting::REJECT_BUTTON_TEXT => $this->isOn($options, 'refuse_opt')
                ? $this->text($options, 'refuse_text')
                : '',
            ...$this->link($options),
            ConsentSetting::POSITION => self::POSITIONS[$this->text($options, 'position')] ?? '',
            ConsentSetting::BACKGROUND_COLOR => $this->conversions->color($options->get(['colors', 'bar'])),
            ConsentSetting::TEXT_COLOR => $this->conversions->color($options->get(['colors', 'text'])),
            ConsentSetting::CONSENT_DURATION => $this->duration($this->text($options, 'time')),
            ConsentSetting::RELOAD => $this->isOn($options, 'redirection') ? '1' : '',
            ConsentSetting::FLOATING_BUTTON => $hasButton ? '1' : '',
            ConsentSetting::FLOATING_BUTTON_LABEL => $hasButton ? $this->text($options, 'revoke_text') : '',
            BlockingSetting::CUSTOM_SCRIPTS => $this->scripts($options),
        ];
    }

    /**
     * The "See more" link: a page of the site, or an address.
     *
     * @param Data $options
     * @return array<string, string>
     */
    private function link(Config $options): array
    {
        if (!$this->isOn($options, 'see_more')) {
            return [];
        }

        $link = BannerLink::Policy;
        $page = $this->text($options, ['see_more_opt', 'link_type']) === 'page'
            ? $this->conversions->page((int) $this->text($options, ['see_more_opt', 'id']))
            : null;
        $url = $page['url'] ?? $this->text($options, ['see_more_opt', 'link']);
        $text = $this->text($options, ['see_more_opt', 'text']);

        return $url === '' ? [] : [
            $link->pageSetting() => $page['slug'] ?? '',
            $link->urlSetting() => \esc_url_raw($url),
            $link->textSetting() => $text !== '' ? $text : $page['title'] ?? '',
            ConsentSetting::LINKS_IN_NEW_TAB => $this->text($options, 'link_target') === '_blank' ? '1' : '',
        ];
    }

    /**
     * The scripts Cookie Notice runs after the accept, in the head and at the end of the body.
     *
     * @param Data $options
     * @return list<array{name: string, category: string, position: string, code: string}>
     */
    private function scripts(Config $options): array
    {
        $scripts = [];
        foreach (self::SCRIPTS as $name => $position) {
            $code = $this->text($options, $name);
            if ($code === '') {
                continue;
            }

            $scripts[] = [
                'name' => $position === CustomScript::HEAD
                    ? \sprintf(
                        /* translators: %s: the name of the other plugin. */
                        \__('%s (head)', 'italy-cookie-choices'),
                        self::SHORT_NAME
                    )
                    : self::SHORT_NAME,
                'category' => Category::MARKETING,
                'position' => $position,
                'code' => $code,
            ];
        }

        return $scripts;
    }

    /**
     * What the Compliance app keeps on the site: the design, the policy link, the custom providers.
     *
     * @return array<string, mixed>
     */
    private function app(): array
    {
        $design = new Config($this->storage->appDesign());
        $blocking = new Config($this->storage->appBlocking());
        $url = $this->text($design, 'privacyPolicyUrl');
        $page = $this->conversions->pageOfUrl($url);
        $link = BannerLink::Policy;

        return [
            ConsentSetting::POSITION => self::POSITIONS[$this->text($design, 'position')] ?? '',
            ConsentSetting::BACKGROUND_COLOR => $this->conversions->color($design->get('bannerColor')),
            $link->pageSetting() => $page['slug'] ?? '',
            $link->urlSetting() => $url !== '' ? \esc_url_raw($url) : '',
            ConsentSetting::GLOBAL_PRIVACY_CONTROL => $this->isOn($blocking, 'gpc_support') ? '1' : '',
            IntegrationSetting::CONSENT_MODE => $blocking->get('google_consent_default') !== null
                && $blocking->get('google_consent_default') !== [] ? '1' : '',
            BlockingSetting::CUSTOM_VENDORS => $this->customProviders($blocking),
        ];
    }

    /**
     * The custom providers of the app with their patterns, as custom vendors.
     *
     * @param Data $blocking
     * @return list<array{id: string, name: string, category: string, patterns: list<string>}>
     */
    private function customProviders(Config $blocking): array
    {
        $patterns = [];
        foreach ((array) $blocking->get('patterns', []) as $pattern) {
            $pattern = new Config(\is_array($pattern) ? $pattern : []);
            if ($this->isOn($pattern, 'IsCustom') && $this->text($pattern, 'Pattern') !== '') {
                $patterns[$this->text($pattern, 'ProviderID')][] = $this->text($pattern, 'Pattern');
            }
        }

        $vendors = [];
        foreach ((array) $blocking->get('providers', []) as $provider) {
            $provider = new Config(\is_array($provider) ? $provider : []);
            $name = $this->text($provider, 'ProviderName');
            $providerPatterns = $patterns[$this->text($provider, 'ProviderID')] ?? [];
            if (!$this->isOn($provider, 'IsCustom') || $name === '' || $providerPatterns === []) {
                continue;
            }

            $vendors[] = [
                'id' => $this->conversions->id($name),
                'name' => $name,
                'category' => self::CATEGORIES[(int) $this->text($provider, 'CategoryID')] ?? Category::MARKETING,
                'patterns' => \array_values(\array_unique($providerPatterns)),
            ];
        }

        return $vendors;
    }

    /**
     * @param Data $options
     * @return list<string>
     */
    private function notes(Config $options): array
    {
        $notes = [];
        if ($this->storage->usesTheApp()) {
            $notes[] = \__(
                'The site uses the Compliance app: its texts and its categories are in the app, '
                . 'write the texts again in Cookie Choices.',
                'italy-cookie-choices'
            );
        }

        if ($this->isOn($options, 'on_scroll') || $this->isOn($options, 'on_click')) {
            $notes[] = \__(
                'The consent on scroll or on click is not imported: it is not a valid consent.',
                'italy-cookie-choices'
            );
        }

        if ($this->isOn($options, 'conditional_active')) {
            $notes[] = \__(
                'The conditional display rules are not imported: '
                . 'list the pages in "Pages where the banner does not open by itself".',
                'italy-cookie-choices'
            );
        }

        if (!$this->storage->usesTheApp() && $this->scripts($options) !== []) {
            $notes[] = \__(
                'The scripts run after the accept wait for the marketing consent: '
                . 'change their category if they are of another one.',
                'italy-cookie-choices'
            );
        }

        return [
            ...$notes,
            \__(
                'Translations are not imported: write the texts again in WPML or Polylang.',
                'italy-cookie-choices'
            ),
            \__(
                'Deactivate Cookie Notice after the import, or the site shows two banners. Its settings stay.',
                'italy-cookie-choices'
            ),
        ];
    }

    /**
     * The duration of the consent closest to an expiration of Cookie Notice.
     */
    private function duration(string $time): string
    {
        $days = self::DURATIONS[$time] ?? null;

        return $days === null ? '' : $this->conversions->consentDuration($days);
    }

    /**
     * @param Data $data
     * @param string|list<string> $key
     */
    private function text(Config $data, string|array $key): string
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
