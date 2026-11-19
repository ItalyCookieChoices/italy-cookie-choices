<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\CookieYes;

use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\BannerLink;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Cookies\Application\CookieSetting;
use ItalyCookieChoices\Migration\Application\ArrayReader;
use ItalyCookieChoices\Migration\Application\Conversions;
use ItalyCookieChoices\Settings\PluginSetting;

/**
 * The settings of the versions 2 of CookieYes, still named GDPR Cookie Consent, not upgraded to version 3.
 */
final readonly class CookieYesLegacy
{
    private const POSITIONS = ['top' => '1', 'bottom' => '3'];
    private const WIDGET_POSITIONS = ['left' => '4', 'right' => '5'];
    private const MODAL = '2';
    private const NOT_NECESSARY = 'non-necessary';

    /**
     * The shortcodes of CookieYes in the message, registered only while it is active.
     */
    private const SHORTCODES = '/\[\/?cookie_[a-z_]+[^\]]*\]/';

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
        $options = $this->storage->legacyOptions();
        $hasButton = $this->reader->isOn($options, 'showagain_tab');

        return \array_filter(
            [
                PluginSetting::ACTIVE => $this->reader->isOn($options, 'is_on') ? '1' : '',
                ConsentSetting::TEXT => $this->message($options),
                ConsentSetting::ACCEPT_BUTTON_TEXT => $this->firstText($options, 'button_7_text', 'button_1_text'),
                ConsentSetting::REJECT_BUTTON_TEXT => $this->reader->text($options, 'button_3_text'),
                ConsentSetting::PREFERENCES_BUTTON_TEXT => $this->reader->text($options, 'button_4_text'),
                ...$this->link($options),
                ConsentSetting::POSITION => $this->position($options),
                ConsentSetting::BACKGROUND_COLOR => $this->conversions->color($options['background'] ?? ''),
                ConsentSetting::TEXT_COLOR => $this->conversions->color($options['text'] ?? ''),
                ConsentSetting::RELOAD => $this->reader->isOn($options, 'accept_close_reload') ? '1' : '',
                ConsentSetting::FLOATING_BUTTON => $hasButton ? '1' : '',
                ConsentSetting::FLOATING_BUTTON_LABEL => $hasButton
                    ? \stripslashes($this->reader->text($options, 'showagain_text'))
                    : '',
                ConsentSetting::FLOATING_BUTTON_POSITION => $hasButton
                    ? ($this->reader->text($options, 'notify_position_horizontal') === 'left' ? 'left' : 'right')
                    : '',
                CookieSetting::SITE_COOKIES => $this->cookies(),
            ],
            static fn(mixed $value): bool => $value !== '' && $value !== []
        );
    }

    /**
     * @return list<string>
     */
    public function notes(): array
    {
        $options = $this->storage->legacyOptions();

        return $this->reader->isOn($options, 'scroll_close')
            ? [\__(
                'The consent on scroll is not imported: it is not a valid consent.',
                'italy-cookie-choices'
            )]
            : [];
    }

    /**
     * The message without the shortcodes of the buttons, like [cookie_accept_all]: the banner has its buttons.
     *
     * @param array<array-key, mixed> $options
     */
    private function message(array $options): string
    {
        $message = \stripslashes($this->reader->text($options, 'notify_message'));
        $message = (string) \preg_replace(self::SHORTCODES, '', $message);

        return \trim((string) \preg_replace('/\s+/', ' ', \wp_kses_post($message)));
    }

    /**
     * @param array<array-key, mixed> $options
     */
    private function firstText(array $options, string ...$names): string
    {
        foreach ($names as $name) {
            $text = $this->reader->text($options, $name);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    /**
     * The "Read more" button: a page of the site, or an address.
     *
     * @param array<array-key, mixed> $options
     * @return array<string, string>
     */
    private function link(array $options): array
    {
        $link = BannerLink::Policy;
        $page = $this->reader->text($options, 'button_2_url_type') === 'page'
            ? $this->conversions->page((int) $this->reader->text($options, 'button_2_page'))
            : $this->conversions->pageOfUrl($this->reader->text($options, 'button_2_url'));
        $url = $page['url'] ?? $this->reader->text($options, 'button_2_url');
        if ($url === '' || $url === '#') {
            return [];
        }

        return [
            $link->pageSetting() => $page['slug'] ?? '',
            $link->urlSetting() => \esc_url_raw($url),
            $link->textSetting() => $this->reader->text($options, 'button_2_text'),
            ConsentSetting::LINKS_IN_NEW_TAB => $this->reader->isOn($options, 'button_2_new_win') ? '1' : '',
        ];
    }

    /**
     * @param array<array-key, mixed> $options
     */
    private function position(array $options): string
    {
        return match ($this->reader->text($options, 'cookie_bar_as')) {
            'popup' => self::MODAL,
            'widget' => self::WIDGET_POSITIONS[$this->reader->text($options, 'widget_position')] ?? '',
            default => self::POSITIONS[$this->reader->text($options, 'notify_position_vertical')] ?? '',
        };
    }

    /**
     * @return list<array{name: string, category: string, duration: string, purpose: string}>
     */
    private function cookies(): array
    {
        $cookies = [];
        foreach ($this->storage->legacyCookies() as $cookie) {
            if ($cookie['name'] === '') {
                continue;
            }

            $cookies[] = [
                'name' => $cookie['name'],
                'category' => $cookie['sensitivity'] === self::NOT_NECESSARY
                    ? Category::MARKETING
                    : Category::NECESSARY,
                'duration' => $cookie['duration'],
                'purpose' => \trim($cookie['purpose']),
            ];
        }

        return $cookies;
    }
}
