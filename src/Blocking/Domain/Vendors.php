<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Domain;

/**
 * The vendors to block, and the text that keeps a script or a frame out of the blocking.
 *
 * @implements \IteratorAggregate<int, Vendor>
 */
final readonly class Vendors implements \IteratorAggregate
{
    /**
     * @var list<Vendor>
     */
    private array $vendors;

    /**
     * @var list<string>
     */
    private array $allowed;

    /**
     * @param list<Vendor> $vendors
     * @param list<string> $allowed Never blocked when the URL or the inline script contains one of them.
     */
    public function __construct(array $vendors, array $allowed = [])
    {
        $this->vendors = $vendors;
        $this->allowed = \array_values(\array_filter(
            \array_map('trim', $allowed),
            static fn(string $text): bool => $text !== ''
        ));
    }

    /**
     * The vendor of a script or a frame URL, null when it is not blocked.
     */
    public function loading(string $url): ?Vendor
    {
        if ($this->isAllowed($url)) {
            return null;
        }

        foreach ($this->vendors as $vendor) {
            if ($vendor->loads($url)) {
                return $vendor;
            }
        }

        return null;
    }

    /**
     * The vendor of an inline script, null when it is not blocked.
     */
    public function running(string $script): ?Vendor
    {
        if ($this->isAllowed($script)) {
            return null;
        }

        foreach ($this->vendors as $vendor) {
            if ($vendor->runs($script)) {
                return $vendor;
            }
        }

        return null;
    }

    /**
     * @return \ArrayIterator<int, Vendor>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->vendors);
    }

    private function isAllowed(string $text): bool
    {
        foreach ($this->allowed as $allowed) {
            if (\str_contains($text, $allowed)) {
                return true;
            }
        }

        return false;
    }
}
