<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Domain;

/**
 * A group of cookies the visitor can accept or refuse, the required ones are always accepted.
 */
final readonly class Category
{
    public const NECESSARY = 'necessary';
    public const PREFERENCES = 'preferences';
    public const STATISTICS = 'statistics';
    public const MARKETING = 'marketing';

    private const ID_PATTERN = '/^[a-z0-9_-]+$/';

    public function __construct(
        private string $id,
        private bool $required = false,
    ) {
        if (\preg_match(self::ID_PATTERN, $id) !== 1) {
            throw new \InvalidArgumentException(\sprintf(
                'The category id "%s" must contain only lowercase letters, numbers, dashes and underscores',
                $id
            ));
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
