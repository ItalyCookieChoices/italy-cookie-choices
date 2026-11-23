<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\UI;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Cookies\Application\CookieSetting;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Settings\PluginSetting;

/**
 * What an import changes, in lines the admin reads before importing.
 */
final readonly class ImportSummary
{
    /**
     * The lines of the summary and the notes to check after the import, for the REST API and the abilities.
     *
     * @return array{summary: list<string>, notes: list<string>}
     */
    public function toArray(ImportedSettings $imported): array
    {
        return ['summary' => $this->lines($imported), 'notes' => $imported->notes()];
    }

    /**
     * @return list<string>
     */
    public function lines(ImportedSettings $imported): array
    {
        $settings = $imported->settings();
        $lines = [];
        foreach ($this->groups() as $label => $names) {
            if (\array_intersect($names, \array_keys($settings)) !== []) {
                $lines[] = $label;
            }
        }

        foreach ($this->counts() as $name => $label) {
            $count = \is_array($settings[$name] ?? null) ? \count($settings[$name]) : 0;
            if ($count > 0) {
                $lines[] = \sprintf($label, $count);
            }
        }

        $vendors = \count($imported->vendorsInUse());
        if ($vendors > 0) {
            $lines[] = \sprintf(
                /* translators: %d: the number of vendors. */
                \_n('%d common vendor turned on', '%d common vendors turned on', $vendors, 'italy-cookie-choices'),
                $vendors
            );
        }

        return $lines;
    }

    /**
     * @return array<string, list<string>>
     */
    private function groups(): array
    {
        return [
            \__('The banner turned on', 'italy-cookie-choices') => [PluginSetting::ACTIVE],
            \__('The texts of the banner and of the buttons', 'italy-cookie-choices') => [
                ConsentSetting::TEXT,
                ConsentSetting::ACCEPT_BUTTON_TEXT,
                ConsentSetting::REJECT_BUTTON_TEXT,
                ConsentSetting::PREFERENCES_BUTTON_TEXT,
                ConsentSetting::SAVE_BUTTON_TEXT,
            ],
            \__('The policy and legal notice links', 'italy-cookie-choices') => [
                ConsentSetting::POLICY_URL,
                ConsentSetting::LEGAL_NOTICE_URL,
            ],
            \__('The texts of the categories', 'italy-cookie-choices') => [ConsentSetting::CATEGORIES],
            \__('The position, the logo, the colors and the custom CSS', 'italy-cookie-choices') => [
                ConsentSetting::POSITION,
                ConsentSetting::LOGO,
                ConsentSetting::BACKGROUND_COLOR,
                ConsentSetting::TEXT_COLOR,
                ConsentSetting::CUSTOM_CSS,
            ],
            \__(
                'The duration of the consent, the cookie domain and the pages without the banner',
                'italy-cookie-choices'
            ) => [
                ConsentSetting::CONSENT_DURATION,
                ConsentSetting::COOKIE_DOMAIN,
                ConsentSetting::EXCLUDED_PAGES,
                ConsentSetting::GLOBAL_PRIVACY_CONTROL,
            ],
            \__('The button to change the consent', 'italy-cookie-choices') => [ConsentSetting::FLOATING_BUTTON],
            \__('The consent per vendor and the placeholder texts', 'italy-cookie-choices') => [
                BlockingSetting::VENDOR_CONSENT,
                BlockingSetting::PLACEHOLDER_TEXT,
                BlockingSetting::PLACEHOLDER_BUTTON_TEXT,
            ],
            \__('Google Consent Mode and the Google tags', 'italy-cookie-choices') => [
                IntegrationSetting::CONSENT_MODE,
                IntegrationSetting::GA_MEASUREMENT_ID,
                IntegrationSetting::GTM_CONTAINER_ID,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function counts(): array
    {
        return [
            /* translators: %d: the number of vendors. */
            BlockingSetting::CUSTOM_VENDORS => \__('Custom vendors: %d', 'italy-cookie-choices'),
            /* translators: %d: the number of scripts. */
            BlockingSetting::CUSTOM_SCRIPTS => \__('Scripts after the consent: %d', 'italy-cookie-choices'),
            /* translators: %d: the number of addresses. */
            BlockingSetting::ALLOWED_PATTERNS => \__('Addresses never blocked: %d', 'italy-cookie-choices'),
            /* translators: %d: the number of cookies. */
            CookieSetting::SITE_COOKIES => \__('Site cookies: %d', 'italy-cookie-choices'),
        ];
    }
}
