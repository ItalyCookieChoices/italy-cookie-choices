<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\UI;

use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyCookieChoices\Integrations\Domain\MicrosoftConsentType;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Prints the consent signals of Microsoft Advertising (UET) and Microsoft Clarity at the start of the head,
 * before their tags.
 *
 * The ad storage of UET is denied by default, Clarity gets the stored consent or a denial,
 * then both get every choice. The script is inline, so it runs before the tags of the theme and of the plugins.
 */
final readonly class MicrosoftConsentScript implements SubscriberInterface
{
    public const ID = 'italy-cookie-choices-microsoft-consent-js';

    private const SCRIPT = 'microsoft-consent.js';

    /**
     * @param string $buildPath The assets/build folder, with the trailing slash.
     */
    public function __construct(
        private IntegrationSettings $settings,
        private ConsentScript $consent,
        private string $buildPath,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_head' => [
            self::CALLBACK => 'print',
            self::PRIORITY => 1,
        ];
    }

    public function print(): void
    {
        $file = $this->buildPath . self::SCRIPT;
        if (!$this->sendsAnySignal() || !\is_readable($file)) {
            return;
        }

        \wp_print_inline_script_tag(
            \sprintf('window.italyCookieChoicesMicrosoftConsent = %s;', (string) \wp_json_encode($this->config()))
            . "\n" . (string) \file_get_contents($file),
            ['id' => self::ID]
        );
    }

    /**
     * The consent types each category grants, null for a signal turned off.
     *
     * @return array{
     *     consent: array<string, mixed>,
     *     uet: array<string, list<string>>|null,
     *     clarity: array<string, list<string>>|null
     * }
     */
    public function config(): array
    {
        return [
            'consent' => $this->consent->config(),
            'uet' => $this->settings->sendsUetConsent() ? $this->uetMapping() : null,
            'clarity' => $this->settings->sendsClarityConsent() ? $this->clarityMapping() : null,
        ];
    }

    private function sendsAnySignal(): bool
    {
        return $this->settings->sendsUetConsent() || $this->settings->sendsClarityConsent();
    }

    /**
     * @return array<string, list<string>>
     */
    private function uetMapping(): array
    {
        return [
            Category::MARKETING => [MicrosoftConsentType::UET_AD_STORAGE->value],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function clarityMapping(): array
    {
        return [
            Category::STATISTICS => [MicrosoftConsentType::CLARITY_ANALYTICS_STORAGE->value],
            Category::MARKETING => [MicrosoftConsentType::CLARITY_AD_STORAGE->value],
        ];
    }
}
