<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\CookieYes;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Migration\Application\Conversions;
use ItalyCookieChoices\Settings\PluginSetting;
use ItalyStrap\Config\Config;

/**
 * The active banner of CookieYes 3 and its settings, in the shape of the settings of Cookie Choices.
 *
 * The texts are read in the default language of CookieYes. A setting CookieYes leaves off keeps the default
 * of Cookie Choices: the import turns features on, not off.
 *
 * @phpstan-type Data Config<array-key, mixed>
 */
final readonly class CookieYesBanner
{
    public const NAME = 'CookieYes';

    private const DEFAULT_LANGUAGE = 'en';

    /**
     * The types and the positions of the banner of CookieYes, as the positions of Cookie Choices.
     */
    private const POSITIONS = [
        'banner' => ['top' => '1', 'bottom' => '3'],
        'classic' => ['top' => '1', 'bottom' => '3'],
        'box' => ['bottom-left' => '4', 'bottom-right' => '5'],
    ];
    private const MODAL = '2';

    private const NOTICE = ['config', 'notice'];
    private const BUTTONS = ['notice', 'elements', 'buttons', 'elements'];
    private const READ_MORE = [...self::NOTICE, 'elements', 'buttons', 'elements', 'readMore'];
    private const LOGO = [...self::NOTICE, 'elements', 'brandLogo'];
    private const REVISIT = ['config', 'revisitConsent'];

    public function __construct(
        private CookieYesStorage $storage,
        private Conversions $conversions,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $banner = $this->storage->banner();
        if ($banner['settings'] === []) {
            return [];
        }

        $config = new Config($banner['settings']);
        $texts = $this->texts($banner['contents']);

        return \array_filter(
            [
                PluginSetting::ACTIVE => '1',
                ...$this->buttonsAndText($texts),
                ...$this->link($config, $texts),
                ...$this->style($config),
                ...$this->behaviour($config, $texts),
                IntegrationSetting::CONSENT_MODE => $this->isOn(new Config($this->storage->consentMode()), ['status'])
                    ? '1'
                    : '',
            ],
            static fn(mixed $value): bool => $value !== ''
        );
    }

    /**
     * @return list<string>
     */
    public function notes(): array
    {
        $config = new Config($this->storage->banner()['settings']);
        $notes = [];
        if ($this->isOn(new Config($this->storage->settings()), ['account', 'connected'])) {
            $notes[] = \__(
                'The site uses the CookieYes web app: '
                . 'its settings in WordPress can be older than the ones of the web app.',
                'italy-cookie-choices'
            );
        }

        if ($this->text($config, ['settings', 'applicableLaw']) === 'ccpa') {
            $notes[] = \__(
                'The banner for the United States, with the opt-out, is not imported: Cookie Choices asks the consent.',
                'italy-cookie-choices'
            );
        }

        if ($this->text($config, ['meta', 'customCSS']) !== '') {
            $notes[] = \__(
                'The custom CSS is imported in a comment: rewrite it for Cookie Choices.',
                'italy-cookie-choices'
            );
        }

        return $notes;
    }

    /**
     * The texts of the default language of CookieYes, or of the first one.
     *
     * @param array<array-key, mixed> $contents
     * @return Data
     */
    private function texts(array $contents): Config
    {
        $language = $this->text(new Config($this->storage->settings()), ['languages', 'default']);
        $texts = $contents[$language !== '' ? $language : self::DEFAULT_LANGUAGE] ?? \reset($contents);

        return new Config(\is_array($texts) ? $texts : []);
    }

    /**
     * @param Data $texts
     * @return array<string, string>
     */
    private function buttonsAndText(Config $texts): array
    {
        return [
            ConsentSetting::TEXT => $this->text($texts, ['notice', 'elements', 'description']),
            ConsentSetting::ACCEPT_BUTTON_TEXT => $this->text($texts, [...self::BUTTONS, 'accept']),
            ConsentSetting::REJECT_BUTTON_TEXT => $this->text($texts, [...self::BUTTONS, 'reject']),
            ConsentSetting::PREFERENCES_BUTTON_TEXT => $this->text($texts, [...self::BUTTONS, 'settings']),
            ConsentSetting::SAVE_BUTTON_TEXT => $this->text(
                $texts,
                ['preferenceCenter', 'elements', 'buttons', 'elements', 'save']
            ),
        ];
    }

    /**
     * The "Read more" link of the notice, to the policy.
     *
     * @param Data $config
     * @param Data $texts
     * @return array<string, string>
     */
    private function link(Config $config, Config $texts): array
    {
        $url = $this->text($texts, ['notice', 'elements', 'privacyLink']);
        if (!$this->isOn($config, [...self::READ_MORE, 'status']) || $url === '' || $url === '#') {
            return [];
        }

        $link = BannerLink::Policy;
        $page = $this->conversions->pageOfUrl($url);

        return [
            $link->pageSetting() => $page['slug'] ?? '',
            $link->urlSetting() => \esc_url_raw($url),
            $link->textSetting() => $this->text($texts, [...self::BUTTONS, 'readMore']),
            ConsentSetting::LINKS_IN_NEW_TAB => $this->isOn($config, [...self::READ_MORE, 'meta', 'newTab']) ? '1' : '',
            ConsentSetting::LINKS_NOFOLLOW => $this->isOn($config, [...self::READ_MORE, 'meta', 'noFollow']) ? '1' : '',
        ];
    }

    /**
     * @param Data $config
     * @return array<string, string>
     */
    private function style(Config $config): array
    {
        $type = $this->text($config, ['settings', 'type']);
        $position = $this->text($config, ['settings', 'position']);
        $css = $this->text($config, ['meta', 'customCSS']);

        return [
            ConsentSetting::POSITION => $type === 'popup' ? self::MODAL : self::POSITIONS[$type][$position] ?? '',
            ConsentSetting::LOGO => $this->isOn($config, [...self::LOGO, 'status'])
                ? $this->conversions->attachmentOfUrl($this->text($config, [...self::LOGO, 'meta', 'url']))
                : '',
            ConsentSetting::BACKGROUND_COLOR => $this->conversions->color(
                $config->get([...self::NOTICE, 'styles', 'background-color'])
            ),
            ConsentSetting::TEXT_COLOR => $this->conversions->color(
                $config->get([...self::NOTICE, 'elements', 'description', 'styles', 'color'])
            ),
            ConsentSetting::CUSTOM_CSS => $css !== '' ? $this->conversions->commentedCss(self::NAME, $css) : '',
        ];
    }

    /**
     * @param Data $config
     * @param Data $texts
     * @return array<string, string>
     */
    private function behaviour(Config $config, Config $texts): array
    {
        $expiry = $config->get(['settings', 'consentExpiry', 'value']);
        $hasRevisit = $this->isOn($config, [...self::REVISIT, 'status']);

        return [
            ConsentSetting::CONSENT_DURATION => $this->isOn($config, ['settings', 'consentExpiry', 'status'])
                && \is_numeric($expiry) ? $this->conversions->consentDuration((int) $expiry) : '',
            ConsentSetting::RELOAD => $this->isOn($config, ['behaviours', 'reloadBannerOnAccept', 'status']) ? '1' : '',
            ConsentSetting::GLOBAL_PRIVACY_CONTROL => $this->isOn($config, ['behaviours', 'respectGPC', 'status'])
                ? '1'
                : '',
            ConsentSetting::FLOATING_BUTTON => $hasRevisit ? '1' : '',
            ConsentSetting::FLOATING_BUTTON_POSITION => $hasRevisit
                ? ($this->text($config, [...self::REVISIT, 'position']) === 'bottom-left' ? 'left' : 'right')
                : '',
            ConsentSetting::FLOATING_BUTTON_LABEL => $hasRevisit
                ? $this->text($texts, ['revisitConsent', 'elements', 'title'])
                : '',
            BlockingSetting::PLACEHOLDER_TEXT => $this->text($texts, ['videoPlaceholder', 'elements', 'title']),
        ];
    }

    /**
     * @param list<string> $path
     * @param Data $data
     */
    private function text(Config $data, array $path): string
    {
        return $this->conversions->text($data->get($path));
    }

    /**
     * @param list<string> $path
     * @param Data $data
     */
    private function isOn(Config $data, array $path): bool
    {
        return $this->conversions->isOn($data->get($path));
    }
}
