<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Domain;

/**
 * The choice of a visitor: the accepted categories, for a policy version, at a date.
 */
final readonly class Consent
{
    /**
     * @param list<string> $categories
     */
    public function __construct(
        private array $categories,
        private PolicyVersion $version,
        private \DateTimeImmutable $givenAt,
    ) {
    }

    public function includes(string $category): bool
    {
        return \in_array($category, $this->categories, true);
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return $this->categories;
    }

    public function version(): PolicyVersion
    {
        return $this->version;
    }

    public function givenAt(): \DateTimeImmutable
    {
        return $this->givenAt;
    }
}
