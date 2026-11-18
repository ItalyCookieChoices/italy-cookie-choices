<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\Domain;

use ItalyCookieChoices\Consent\Domain\PolicyVersion;

/**
 * A line of the log: the categories a visitor accepted, for a policy version, when the site received them.
 *
 * No IP address, no user agent and no account: the random id is enough to show the history of a choice.
 * No category means the consent was withdrawn. With the consent per vendor, the line also has
 * the vendors refused inside the accepted categories.
 */
final readonly class ConsentRecord
{
    /**
     * @param list<string> $categories
     * @param list<string> $refusedVendors
     */
    public function __construct(
        private ConsentId $id,
        private array $categories,
        private PolicyVersion $version,
        private \DateTimeImmutable $recordedAt,
        private array $refusedVendors = [],
    ) {
    }

    public function id(): ConsentId
    {
        return $this->id;
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return $this->categories;
    }

    public function isWithdrawal(): bool
    {
        return $this->categories === [];
    }

    public function version(): PolicyVersion
    {
        return $this->version;
    }

    public function recordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }

    /**
     * The vendors refused inside the accepted categories, with the consent per vendor.
     *
     * @return list<string>
     */
    public function refusedVendors(): array
    {
        return $this->refusedVendors;
    }
}
