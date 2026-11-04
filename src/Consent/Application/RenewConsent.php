<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Application;

use ItalyCookieChoices\Consent\Domain\ConsentRepository;
use ItalyCookieChoices\Consent\Domain\ConsentStorage;

/**
 * Keeps the consent of the visitor for its full duration, when the policy still accepts it.
 */
final readonly class RenewConsent
{
    public function __construct(
        private ConsentPolicyFactory $policy,
        private ConsentRepository $consents,
        private ConsentStorage $storage,
    ) {
    }

    /**
     * @return bool Whether the consent was renewed.
     */
    public function __invoke(\DateTimeImmutable $now): bool
    {
        $consent = $this->consents->current();
        $policy = $this->policy->create();
        if ($consent === null || !$policy->accepts($consent, $now)) {
            return false;
        }

        $this->storage->renew($policy->duration()->expiresAt($consent->givenAt()));

        return true;
    }
}
