<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Domain;

/**
 * The pages to scan, the home page has the id 0.
 */
final readonly class Targets
{
    /**
     * @param list<array{id: int, title: string, url: string}> $targets
     * @param bool $truncated Whether the selection had more pages than the limit.
     */
    public function __construct(
        private array $targets,
        private bool $truncated,
    ) {
    }

    /**
     * @return list<array{id: int, title: string, url: string}>
     */
    public function targets(): array
    {
        return $this->targets;
    }

    public function isTruncated(): bool
    {
        return $this->truncated;
    }
}
