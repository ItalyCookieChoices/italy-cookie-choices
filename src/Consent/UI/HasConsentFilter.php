<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Consent\Domain\ConsentRepository;
use ItalyCookieChoices\Consent\Infrastructure\FilteredConsentPolicy;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Lets themes and plugins check the consent in PHP without depending on the plugin classes:
 *
 * apply_filters( 'italy_cookie_choices_has_consent', false, 'statistics' );
 *
 * The answer depends on the visitor, a page that uses it must not be cached.
 */
final readonly class HasConsentFilter implements SubscriberInterface
{
    public const FILTER = 'italy_cookie_choices_has_consent';

    public function __construct(
        private FilteredConsentPolicy $policy,
        private ConsentRepository $consents,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield self::FILTER => [
            self::CALLBACK => 'hasConsent',
            self::ACCEPTED_ARGS => 2,
        ];
    }

    public function hasConsent(mixed $hasConsent, mixed $category = ''): bool
    {
        if (!\is_string($category) || $category === '') {
            return (bool) $hasConsent;
        }

        return $this->policy->create()->allows($this->consents->current(), $category, new \DateTimeImmutable());
    }
}
