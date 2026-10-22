<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Infrastructure;

use ItalyCookieChoices\Consent\Domain\Consent;
use ItalyCookieChoices\Consent\Domain\ConsentRepository;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;

/**
 * Reads the consent from the cookie written by assets/js/consent.js.
 *
 * The cookie holds a JSON object: {"version": "1", "categories": ["necessary"], "date": "2026-01-31T10:00:00.000Z"}
 */
final readonly class CookieConsentRepository implements ConsentRepository
{
    public const COOKIE_NAME = 'icc_consent';

    /**
     * @param array<array-key, mixed> $cookies The request cookies, slashed by WordPress.
     */
    public function __construct(
        private array $cookies,
    ) {
    }

    public function current(): ?Consent
    {
        $value = $this->cookies[self::COOKIE_NAME] ?? null;
        if (!\is_string($value)) {
            return null;
        }

        $data = \json_decode(\wp_unslash($value), true);
        if (!\is_array($data)) {
            return null;
        }

        return $this->consentFrom($data);
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function consentFrom(array $data): ?Consent
    {
        $version = $data['version'] ?? null;
        $categories = $data['categories'] ?? null;
        $date = $data['date'] ?? null;

        if (!\is_string($version) || \trim($version) === '' || !\is_string($date)) {
            return null;
        }

        if (!$this->isListOfStrings($categories)) {
            return null;
        }

        $givenAt = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC3339_EXTENDED, $date);
        if ($givenAt === false) {
            return null;
        }

        /** @var list<string> $categories */
        return new Consent($categories, new PolicyVersion($version), $givenAt);
    }

    private function isListOfStrings(mixed $value): bool
    {
        if (!\is_array($value) || !\array_is_list($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!\is_string($item)) {
                return false;
            }
        }

        return true;
    }
}
