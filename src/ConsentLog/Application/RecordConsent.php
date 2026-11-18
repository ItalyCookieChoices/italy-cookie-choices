<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\Application;

use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\ConsentLog\Domain\ConsentId;
use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyCookieChoices\ConsentLog\Domain\ConsentRecord;

/**
 * Adds a choice sent by the browser to the log, only when it fits the current policy.
 */
final readonly class RecordConsent
{
    public function __construct(
        private ConsentPolicyFactory $policy,
        private ConsentLog $log,
    ) {
    }

    private const VENDOR_ID = '/^[a-z0-9_-]{1,64}$/';

    /**
     * @param list<string> $categories The accepted categories, none when the consent is withdrawn.
     * @param list<string> $refusedVendors The vendors refused inside the accepted categories.
     * @throws \InvalidArgumentException When the choice does not fit the current policy.
     */
    public function __invoke(
        string $id,
        array $categories,
        string $version,
        \DateTimeImmutable $now,
        array $refusedVendors = [],
    ): ConsentRecord {
        $policy = $this->policy->create();
        $policyVersion = new PolicyVersion($version);
        if (!$policyVersion->equals($policy->version())) {
            throw new \InvalidArgumentException('The consent is for another version of the policy');
        }

        $known = $policy->categories()->ids();
        $unknown = \array_diff($categories, $known);
        if ($unknown !== []) {
            throw new \InvalidArgumentException(\sprintf('Unknown categories: %s', \implode(', ', $unknown)));
        }

        $invalid = \array_filter(
            $refusedVendors,
            static fn(string $vendor): bool => \preg_match(self::VENDOR_ID, $vendor) !== 1
        );
        if ($invalid !== []) {
            throw new \InvalidArgumentException('The refused vendors are not valid');
        }

        $record = new ConsentRecord(
            new ConsentId($id),
            \array_values(\array_intersect($known, $categories)),
            $policyVersion,
            $now,
            \array_values(\array_unique($refusedVendors))
        );
        $this->log->add($record);

        return $record;
    }
}
