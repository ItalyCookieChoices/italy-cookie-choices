<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\UI;

use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\Integrations\Application\ConsentModeMapping;
use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Prints the Google Consent Mode v2 script at the start of the head, before the Google tags.
 *
 * Every consent type is denied by default, the stored consent and each change update the granted ones.
 * The script is inline, so it runs before the tags of the theme and of the other plugins.
 */
final readonly class ConsentModeScript implements SubscriberInterface
{
    public const ID = 'italy-cookie-choices-consent-mode-js';

    private const SCRIPT = 'consent-mode.js';

    /**
     * How long the Google tags wait for the update of the stored consent, in milliseconds.
     */
    private const WAIT_FOR_UPDATE = 500;

    /**
     * @param string $buildPath The assets/build folder, with the trailing slash.
     */
    public function __construct(
        private IntegrationSettings $settings,
        private ConsentModeMapping $mapping,
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
        if (!$this->settings->sendsConsentMode() || !\is_readable($file)) {
            return;
        }

        \wp_print_inline_script_tag(
            \sprintf('window.italyCookieChoicesConsentMode = %s;', (string) \wp_json_encode($this->config()))
            . "\n" . (string) \file_get_contents($file),
            ['id' => self::ID]
        );
    }

    /**
     * @return array{consent: array<string, mixed>, mapping: array<string, list<string>>, waitForUpdate: int}
     */
    public function config(): array
    {
        return [
            'consent' => $this->consent->config(),
            'mapping' => $this->mapping->toArray(),
            'waitForUpdate' => self::WAIT_FOR_UPDATE,
        ];
    }
}
