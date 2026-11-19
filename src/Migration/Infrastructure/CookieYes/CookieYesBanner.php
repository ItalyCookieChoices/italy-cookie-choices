<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\CookieYes;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Migration\Application\ArrayReader;
use ItalyCookieChoices\Migration\Application\Conversions;
use ItalyCookieChoices\Settings\PluginSetting;

/**
 * The active banner of CookieYes 3 and its settings, in the shape of the settings of Cookie Choices.
 *
 * The texts are read in the default language of CookieYes. A setting CookieYes leaves off keeps the default
 * of Cookie Choices: the import turns features on, not off.
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

    public function __construct(
        private CookieYesStorage $storage,
        private Conversions $conversions,
        private ArrayReader $reader,
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

        $config = $banner['settings'];
        $texts = $this->texts($banner['contents']);

        return \array_filter(
            [
                PluginSetting::ACTIVE => '1',
                ...$this->buttonsAndText($texts),
                ...$this->link($config, $texts),
                ...$this->style($config),
                ...$this->behaviour($config, $texts),
                IntegrationSetting::CONSENT_MODE => $this->reader->isOn($this->storage->consentMode(), 'status')
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
        $config = $this->storage->banner()['settings'];
        $notes = [];
        if ($this->reader->isOn($this->storage->settings(), 'account', 'connected')) {
            $notes[] = \__(
                'The site uses the CookieYes web app: '
                . 'its settings in WordPress can be older than the ones of the web app.',
                'italy-cookie-choices'
            );
        }

        if ($this->reader->text($config, 'settings', 'applicableLaw') === 'ccpa') {
            $notes[] = \__(
                'The banner for the United States, with the opt-out, is not imported: Cookie Choices asks the consent.',
                'italy-cookie-choices'
            );
        }

        if ($this->reader->text($config, 'meta', 'customCSS') !== '') {
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
     * @return array<array-key, mixed>
     */
    private function texts(array $contents): array
    {
        $language = $this->reader->text($this->storage->settings(), 'languages', 'default');
        $texts = $this->reader->array($contents, $language !== '' ? $language : self::DEFAULT_LANGUAGE);
        if ($texts !== []) {
            return $texts;
        }

        $first = \reset($contents);

        return \is_array($first) ? $first : [];
    }

    /**
     * @param array<array-key, mixed> $texts
     * @return array<string, string>
     */
    private function buttonsAndText(array $texts): array
    {
        $buttons = $this->reader->array($texts, 'notice', 'elements', 'buttons', 'elements');

        return [
            ConsentSetting::TEXT => $this->reader->text($texts, 'notice', 'elements', 'description'),
            ConsentSetting::ACCEPT_BUTTON_TEXT => $this->reader->text($buttons, 'accept'),
            ConsentSetting::REJECT_BUTTON_TEXT => $this->reader->text($buttons, 'reject'),
            ConsentSetting::PREFERENCES_BUTTON_TEXT => $this->reader->text($buttons, 'settings'),
            ConsentSetting::SAVE_BUTTON_TEXT => $this->reader->text(
                $texts,
                'preferenceCenter',
                'elements',
                'buttons',
                'elements',
                'save'
            ),
        ];
    }

    /**
     * The "Read more" link of the notice, to the policy.
     *
     * @param array<array-key, mixed> $config
     * @param array<array-key, mixed> $texts
     * @return array<string, string>
     */
    private function link(array $config, array $texts): array
    {
        $readMore = $this->reader->array($config, 'config', 'notice', 'elements', 'buttons', 'elements', 'readMore');
        $url = $this->reader->text($texts, 'notice', 'elements', 'privacyLink');
        if (!$this->reader->isOn($readMore, 'status') || $url === '' || $url === '#') {
            return [];
        }

        $link = BannerLink::Policy;
        $page = $this->conversions->pageOfUrl($url);

        return [
            $link->pageSetting() => $page['slug'] ?? '',
            $link->urlSetting() => \esc_url_raw($url),
            $link->textSetting() => $this->reader->text(
                $texts,
                'notice',
                'elements',
                'buttons',
                'elements',
                'readMore'
            ),
            ConsentSetting::LINKS_IN_NEW_TAB => $this->reader->isOn($readMore, 'meta', 'newTab') ? '1' : '',
            ConsentSetting::LINKS_NOFOLLOW => $this->reader->isOn($readMore, 'meta', 'noFollow') ? '1' : '',
        ];
    }

    /**
     * @param array<array-key, mixed> $config
     * @return array<string, string>
     */
    private function style(array $config): array
    {
        $type = $this->reader->text($config, 'settings', 'type');
        $position = $this->reader->text($config, 'settings', 'position');
        $logo = $this->reader->array($config, 'config', 'notice', 'elements', 'brandLogo');
        $css = $this->reader->text($config, 'meta', 'customCSS');

        return [
            ConsentSetting::POSITION => $type === 'popup' ? self::MODAL : self::POSITIONS[$type][$position] ?? '',
            ConsentSetting::LOGO => $this->reader->isOn($logo, 'status')
                ? $this->conversions->attachmentOfUrl($this->reader->text($logo, 'meta', 'url'))
                : '',
            ConsentSetting::BACKGROUND_COLOR => $this->conversions->color(
                $this->reader->value($config, 'config', 'notice', 'styles', 'background-color')
            ),
            ConsentSetting::TEXT_COLOR => $this->conversions->color(
                $this->reader->value($config, 'config', 'notice', 'elements', 'description', 'styles', 'color')
            ),
            ConsentSetting::CUSTOM_CSS => $css !== '' ? $this->conversions->commentedCss(self::NAME, $css) : '',
        ];
    }

    /**
     * @param array<array-key, mixed> $config
     * @param array<array-key, mixed> $texts
     * @return array<string, string>
     */
    private function behaviour(array $config, array $texts): array
    {
        $expiry = $this->reader->value($config, 'settings', 'consentExpiry', 'value');
        $revisit = $this->reader->array($config, 'config', 'revisitConsent');
        $hasRevisit = $this->reader->isOn($revisit, 'status');

        return [
            ConsentSetting::CONSENT_DURATION => $this->reader->isOn($config, 'settings', 'consentExpiry', 'status')
                && \is_numeric($expiry) ? $this->conversions->consentDuration((int) $expiry) : '',
            ConsentSetting::RELOAD => $this->reader->isOn($config, 'behaviours', 'reloadBannerOnAccept', 'status')
                ? '1'
                : '',
            ConsentSetting::GLOBAL_PRIVACY_CONTROL => $this->reader->isOn($config, 'behaviours', 'respectGPC', 'status')
                ? '1'
                : '',
            ConsentSetting::FLOATING_BUTTON => $hasRevisit ? '1' : '',
            ConsentSetting::FLOATING_BUTTON_POSITION => $hasRevisit
                ? ($this->reader->text($revisit, 'position') === 'bottom-left' ? 'left' : 'right')
                : '',
            ConsentSetting::FLOATING_BUTTON_LABEL => $hasRevisit
                ? $this->reader->text($texts, 'revisitConsent', 'elements', 'title')
                : '',
            BlockingSetting::PLACEHOLDER_TEXT => $this->reader->text($texts, 'videoPlaceholder', 'elements', 'title'),
        ];
    }
}
