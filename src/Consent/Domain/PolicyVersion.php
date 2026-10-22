<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Domain;

/**
 * The version of the cookie policy, a consent given for another version is asked again.
 */
final readonly class PolicyVersion
{
    public function __construct(
        private string $value,
    ) {
        if (\trim($value) === '') {
            throw new \InvalidArgumentException('The policy version cannot be empty');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
