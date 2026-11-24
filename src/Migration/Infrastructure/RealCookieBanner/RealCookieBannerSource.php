<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\RealCookieBanner;

use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Migration\Application\Conversions;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Settings\PluginSetting;

/**
 * Imports the settings of Real Cookie Banner: the banner, its texts, links and style, the sticky links,
 * the service groups, the services and the content blockers.
 *
 * Only its settings in the database are read, nothing of the plugin is used.
 */
final readonly class RealCookieBannerSource implements MigrationSource
{
    public const ID = 'real-cookie-banner';
    public const NAME = 'Real Cookie Banner';

    private const OPTION = 'rcb-';
    /** The types of the legal links, as the links of the banner. */
    private const LINKS = ['privacyPolicy' => BannerLink::Policy, 'legalNotice' => BannerLink::LegalNotice];
    private const BAR = ['top' => '1', 'bottom' => '3'];
    private const BOX = ['bottomLeft' => '4', 'bottomRight' => '5'];
    private const MODAL = '2';
    private const BANNER_LAYOUT = 'banner';
    /** The tags of the links in the description, like {{privacyPolicy}}privacy policy{{/privacyPolicy}}. */
    private const DESCRIPTION_TAGS = '/\{\{\/?[A-Za-z]+\}\}/';

    public function __construct(
        private RealCookieBannerStorage $storage,
        private RealCookieBannerServices $services,
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
        $services = $this->services->read();
        $css = $this->conversions->commentedCss(self::NAME, $this->text('banner-custom-css-css'));

        return new ImportedSettings(
            \array_filter(
                [
                    PluginSetting::ACTIVE => $this->isOn('banner-active') ? '1' : '',
                    ConsentSetting::TEXT => \wp_kses_post(
                        (string) \preg_replace(self::DESCRIPTION_TAGS, '', $this->text('banner-texts-description'))
                    ),
                    ConsentSetting::ACCEPT_BUTTON_TEXT => $this->text('banner-texts-accept-all'),
                    ConsentSetting::REJECT_BUTTON_TEXT => $this->text('banner-texts-accept-essentials'),
                    ConsentSetting::PREFERENCES_BUTTON_TEXT => $this->text('banner-texts-accept-individual'),
                    ConsentSetting::SAVE_BUTTON_TEXT => $this->text('individual-texts-save'),
                    ...$this->links(),
                    ...$this->style($css),
                    ...$this->behaviour(),
                    ...$services['settings'],
                ],
                static fn(mixed $value): bool => $value !== '' && $value !== []
            ),
            $services['vendorsInUse'],
            \array_values(\array_filter([
                $services['skipped'] === [] ? '' : \sprintf(
                    /* translators: %s: the names of the services, separated by commas. */
                    \__(
                        'These services are not imported, they have no blocker rules '
                            . 'or their code needs Real Cookie Banner: %s.',
                        'italy-cookie-choices'
                    ),
                    \implode(', ', $services['skipped'])
                ),
                $css === '' ? '' : $this->conversions->customCssNote(),
                \__(
                    'The headline, the texts of the content blockers, the IAB TCF, the consent forwarding '
                        . 'and the country bypass are not imported.',
                    'italy-cookie-choices'
                ),
                \__(
                    'Deactivate Real Cookie Banner after the import, or the site shows two banners. Its settings stay.',
                    'italy-cookie-choices'
                ),
            ]))
        );
    }

    /**
     * The privacy policy and the legal notice, from the published legal links, and the pages where the banner
     * does not open by itself.
     *
     * @return array<string, string>
     */
    private function links(): array
    {
        $settings = [];
        $hidden = [];
        foreach ($this->storage->posts(RealCookieBannerStorage::LINKS) as $post) {
            $isExternal = $this->conversions->isOn($this->storage->meta($post['id'], 'isExternalUrl'));
            $page = $isExternal ? null : $this->conversions->page((int) $this->meta($post['id'], 'pageId'));
            if ($page !== null && $this->conversions->isOn($this->storage->meta($post['id'], 'hideCookieBanner'))) {
                $hidden[] = $page['slug'];
            }

            $link = self::LINKS[$this->meta($post['id'], 'pageType')] ?? null;
            $url = $page['url'] ?? ($isExternal ? $this->meta($post['id'], 'externalUrl') : '');
            if ($link === null || $url === '' || \array_key_exists($link->urlSetting(), $settings)) {
                continue;
            }

            $settings[$link->pageSetting()] = $page['slug'] ?? '';
            $settings[$link->urlSetting()] = \esc_url_raw($url);
            $settings[$link->textSetting()] = $post['title'];
        }

        return [...$settings, ConsentSetting::EXCLUDED_PAGES => \implode(', ', \array_unique($hidden))];
    }

    /**
     * @return array<string, string>
     */
    private function style(string $css): array
    {
        return [
            ConsentSetting::POSITION => $this->position(),
            ConsentSetting::LOGO => $this->conversions->attachmentOfUrl($this->text('banner-header-design-logo')),
            ConsentSetting::BACKGROUND_COLOR => $this->conversions->color($this->option('banner-design-bg')),
            ConsentSetting::TEXT_COLOR => $this->conversions->color($this->option('banner-design-font-color')),
            ConsentSetting::CUSTOM_CSS => $css,
        ];
    }

    /**
     * The bars at the top or at the bottom, the dialogs in a bottom corner as boxes, the others in the middle.
     */
    private function position(): string
    {
        if ($this->text('banner-layout-type') === self::BANNER_LAYOUT) {
            return self::BAR[$this->text('banner-layout-banner-position')] ?? self::BAR['bottom'];
        }

        return self::BOX[$this->text('banner-layout-dialog-position')] ?? self::MODAL;
    }

    /**
     * @return array<string, string>
     */
    private function behaviour(): array
    {
        $duration = $this->option('cookie-duration');
        $hasStickyLinks = $this->isOn('banner-sticky-links-enabled');

        return [
            ConsentSetting::CONSENT_DURATION => \is_numeric($duration)
                ? $this->conversions->consentDuration((int) $duration)
                : '',
            ConsentSetting::FLOATING_BUTTON => $hasStickyLinks ? '1' : '',
            ConsentSetting::FLOATING_BUTTON_POSITION => $hasStickyLinks
                ? ($this->text('banner-sticky-links-alignment') === 'left' ? 'left' : 'right')
                : '',
            IntegrationSetting::CONSENT_MODE => $this->isOn('gcm') ? '1' : '',
        ];
    }

    private function option(string $name): mixed
    {
        return $this->storage->option(self::OPTION . $name);
    }

    private function text(string $name): string
    {
        return $this->conversions->text($this->option($name));
    }

    private function isOn(string $name): bool
    {
        return $this->conversions->isOn($this->option($name));
    }

    private function meta(int $post, string $key): string
    {
        return $this->conversions->text($this->storage->meta($post, $key));
    }
}
