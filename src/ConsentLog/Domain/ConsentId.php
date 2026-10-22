<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\Domain;

/**
 * The random id the browser gives to a consent, it identifies the choices of a visitor without saying who it is.
 */
final readonly class ConsentId
{
    private const UUID_V4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    private string $value;

    public function __construct(string $value)
    {
        $value = \strtolower($value);
        if (\preg_match(self::UUID_V4, $value) !== 1) {
            throw new \InvalidArgumentException('The consent id must be a version 4 UUID');
        }

        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
