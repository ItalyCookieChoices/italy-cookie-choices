<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\UI;

use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Blocking\Infrastructure\HtmlBlocker;
use ItalyCookieChoices\Blocking\UI\BlockableRequest;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Sends the Amazon Consent Signal to the Amazon Ad Tag: the consent helper of Amazon and its call,
 * printed at the start of the head and blocked until the marketing consent.
 *
 * The blocked scripts run in the page order after the consent, so the signal reaches Amazon before amzn.js,
 * printed later by the theme or by a plugin. Without the consent nothing loads from Amazon:
 * the tag is blocked too, by the Amazon Ads preset.
 */
final readonly class AmazonConsentScript implements SubscriberInterface
{
    public const HELPER = 'https://c.amazon-adsystem.com/aat/amzn-consent.js';

    // Like the Microsoft signals, before the scripts of the head.
    private const START_OF_THE_HEAD = 2;

    public function __construct(
        private IntegrationSettings $settings,
        private HtmlBlocker $blocker,
        private VendorsFactory $vendors,
        private BlockableRequest $request,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield Hook::HEAD => [
            self::CALLBACK => 'print',
            self::PRIORITY => self::START_OF_THE_HEAD,
        ];
    }

    public function print(): void
    {
        if (!$this->settings->sendsAmazonConsent() || !$this->request->isBlockable()) {
            return;
        }

        $signal = [
            'countryCode' => $this->settings->amazonCountry(),
            'enableAdStorage' => true,
            'enableUserData' => true,
        ];
        $html = \wp_get_script_tag(['src' => self::HELPER])
            . \wp_get_inline_script_tag(
                \sprintf('window.amznConsent && window.amznConsent(%s);', (string) \wp_json_encode($signal))
            );

        // phpcs:ignore WordPress.Security.EscapeOutput -- Built by the core functions, only its attributes change.
        echo $this->blocker->block($html, $this->vendors->create(), Category::MARKETING, VendorId::AMAZON_ADS);
    }
}
