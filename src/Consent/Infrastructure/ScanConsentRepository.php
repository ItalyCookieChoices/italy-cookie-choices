<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Infrastructure;

use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Domain\Consent;
use ItalyCookieChoices\Consent\Domain\ConsentRepository;
use ItalyCookieChoices\Scan\UI\ScanRequest;

/**
 * The consent of the cookie, or every category accepted in memory for a page in scan mode.
 */
final readonly class ScanConsentRepository implements ConsentRepository
{
    public function __construct(
        private CookieConsentRepository $cookie,
        private ScanRequest $scan,
        private ConsentPolicyFactory $policy,
    ) {
    }

    public function current(): ?Consent
    {
        if (!$this->scan->isScan()) {
            return $this->cookie->current();
        }

        $policy = $this->policy->create();

        return new Consent($policy->categories()->ids(), $policy->version(), new \DateTimeImmutable());
    }
}
