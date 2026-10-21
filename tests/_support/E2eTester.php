<?php

declare(strict_types=1);

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class E2eTester extends \Codeception\Actor
{
    use _generated\E2eTesterActions;

    public const OPTION_NAME = 'italy_cookie_choices';

    public const COOKIE_NAME = 'displayCookieConsent';

    public const COOKIE_VALUE = 'y';

    /**
     * Store the plugin options, merged over a minimal active configuration.
     * A null value removes the option key.
     */
    public function haveCookieChoicesOptions(array $options): void
    {
        $this->haveOptionInDatabase(
            self::OPTION_NAME,
            \array_filter(
                \array_merge($this->defaultOptions(), $options),
                static fn($value): bool => $value !== null
            )
        );
    }

    /**
     * Click a submit button and wait for the new page, a plain click can return before the navigation.
     *
     * @param string|array<string, string> $button
     */
    public function submitAndWaitForReload($button): void
    {
        $this->executeJS('window.iccBeforeSubmit = true;');
        $this->click($button);
        $this->waitForJS('return window.iccBeforeSubmit === undefined && document.readyState === "complete";', 15);
    }

    private function defaultOptions(): array
    {
        return [
            'active' => '1',
            'banner' => '1',
            'text' => 'This site uses cookies',
            'url' => 'http://wordpress/privacy-policy/',
            'anchor_text' => 'Read more',
            'button_text' => 'Accept',
            'cookie_name' => self::COOKIE_NAME,
            'cookie_value' => self::COOKIE_VALUE,
            'banner_bg' => '#fff',
            'banner_text_color' => '#000',
            'bannerStyle' => 'bannerStyle',
            'contentStyle' => 'contentStyle',
            'consentText' => 'consentText',
            'infoClass' => 'italybtn',
            'closeClass' => 'italybtn',
            'content_message_text' => 'Content blocked',
            'content_message_button_text' => 'Allow',
        ];
    }
}
