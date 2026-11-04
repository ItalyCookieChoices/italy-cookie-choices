<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\UI;

use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Domain\ConsentRepository;
use ItalyCookieChoices\Integrations\Application\WpConsentApiMapping;
use ItalyCookieChoices\Settings\Options;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Answers the WP Consent API in PHP with the consent of the plugin, while the banner is on.
 *
 * Without a consent type the API allows every category, so the plugin declares the opt-in.
 * The API reads its own cookies, written in the browser: the filter answers from the consent cookie
 * of the plugin, so an expired or withdrawn consent is never allowed.
 */
final readonly class WpConsentApiFilters implements SubscriberInterface
{
    private const CONSENT_TYPE = 'optin';

    public function __construct(
        private Options $options,
        private WpConsentApiMapping $mapping,
        private ConsentPolicyFactory $policy,
        private ConsentRepository $consents,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_get_consent_type' => 'consentType';
        yield 'wp_has_consent' => [
            self::CALLBACK => 'hasConsent',
            self::ACCEPTED_ARGS => 2,
        ];
        yield 'wp_consent_api_cookie_expiration' => 'cookieExpiration';
    }

    public function consentType(mixed $consentType): mixed
    {
        return $this->options->isActive() ? self::CONSENT_TYPE : $consentType;
    }

    /**
     * @param mixed $apiCategory A category of the WP Consent API.
     */
    public function hasConsent(mixed $hasConsent, mixed $apiCategory = ''): bool
    {
        if (!\is_string($apiCategory) || !$this->options->isActive()) {
            return (bool) $hasConsent;
        }

        $categories = $this->mapping->categoriesAllowing($apiCategory);
        if ($categories === []) {
            return (bool) $hasConsent;
        }

        return $this->allowsAny($categories);
    }

    /**
     * The cookies of the API last as the consent of the plugin.
     */
    public function cookieExpiration(mixed $days): mixed
    {
        return $this->options->isActive() ? $this->policy->create()->duration()->days() : $days;
    }

    /**
     * @param non-empty-list<string> $categories
     */
    private function allowsAny(array $categories): bool
    {
        $policy = $this->policy->create();
        $consent = $this->consents->current();
        $now = new \DateTimeImmutable();

        foreach ($categories as $category) {
            if ($policy->allows($consent, $category, $now)) {
                return true;
            }
        }

        return false;
    }
}
