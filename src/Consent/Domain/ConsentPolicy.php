<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Domain;

/**
 * The rules a consent follows: the categories, the policy version and the duration.
 */
final readonly class ConsentPolicy
{
    public function __construct(
        private Categories $categories,
        private PolicyVersion $version,
        private ConsentDuration $duration,
    ) {
    }

    public function categories(): Categories
    {
        return $this->categories;
    }

    public function version(): PolicyVersion
    {
        return $this->version;
    }

    public function duration(): ConsentDuration
    {
        return $this->duration;
    }

    /**
     * A consent counts when it was given for this policy version and it is not expired.
     */
    public function accepts(Consent $consent, \DateTimeImmutable $now): bool
    {
        return $consent->version()->equals($this->version)
            && $this->duration->expiresAt($consent->givenAt()) > $now;
    }

    /**
     * The required categories are allowed even without a consent.
     */
    public function allows(?Consent $consent, string $category, \DateTimeImmutable $now): bool
    {
        if (\in_array($category, $this->categories->requiredIds(), true)) {
            return true;
        }

        if ($consent === null || !$this->accepts($consent, $now)) {
            return false;
        }

        return $this->categories->has($category) && $consent->includes($category);
    }
}
