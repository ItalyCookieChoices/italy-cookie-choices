<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Application;

use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Blocking\Domain\Vendors;

/**
 * Dispatched while the vendors to block are collected, listeners add, replace or remove them:
 *
 * add_action( BuildingVendors::class, static function ( BuildingVendors $event ): void {
 *     $event->addVendor( new Vendor( 'matomo', 'statistics', [ 'matomo.example.com' ], [ '_paq.push' ] ) );
 *     $event->removeVendor( 'vimeo' );
 *     $event->allow( 'youtube.com/embed/public-video' );
 * } );
 */
final class BuildingVendors
{
    /**
     * @var array<string, Vendor>
     */
    private array $vendors = [];

    /**
     * @var list<string>
     */
    private array $allowed;

    /**
     * @param list<Vendor> $vendors
     * @param list<string> $allowed
     */
    public function __construct(array $vendors, array $allowed = [])
    {
        foreach ($vendors as $vendor) {
            $this->addVendor($vendor);
        }

        $this->allowed = $allowed;
    }

    /**
     * Adds the vendor at the end, or replaces the one with the same id in its place.
     */
    public function addVendor(Vendor $vendor): void
    {
        $this->vendors[$vendor->id()] = $vendor;
    }

    public function removeVendor(string $id): void
    {
        unset($this->vendors[$id]);
    }

    /**
     * Scripts and frames whose URL or inline code contains the text are never blocked.
     */
    public function allow(string $text): void
    {
        $this->allowed[] = $text;
    }

    public function vendors(): Vendors
    {
        return new Vendors(\array_values($this->vendors), $this->allowed);
    }
}
