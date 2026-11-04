<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Application;

use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Blocking\Domain\Vendors;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Settings\Options;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Collects the vendors to block from the presets not turned off, the Google tag gateway, the custom vendors
 * and the listeners of BuildingVendors.
 */
final readonly class VendorsFactory
{
    private const GOOGLE_TAG_GATEWAY = 'google-tag-gateway';

    public function __construct(
        private VendorPresets $presets,
        private EventDispatcherInterface $dispatcher,
        private Options $options,
    ) {
    }

    public function create(): Vendors
    {
        $event = new BuildingVendors(
            [...$this->presets(), ...$this->googleTagGateway(), ...$this->customVendors()],
            $this->options->values('allowed_patterns')
        );
        $this->dispatcher->dispatch($event);

        return $event->vendors();
    }

    /**
     * @return list<Vendor>
     */
    private function presets(): array
    {
        $disabled = $this->options->values('disabled_vendors');

        return \array_values(\array_filter(
            $this->presets->all(),
            static fn(Vendor $vendor): bool => !\in_array($vendor->id(), $disabled, true)
        ));
    }

    /**
     * The Google tags served from a path of the site by the Google tag gateway wait for the marketing consent.
     * The presets come first: the ids in the address, like G-ABC123, keep Google Analytics in its category.
     *
     * @return list<Vendor>
     */
    private function googleTagGateway(): array
    {
        $path = $this->options->text('google_tag_gateway_path');
        if ($path === '') {
            return [];
        }

        $host = (string) \wp_parse_url(\home_url(), \PHP_URL_HOST);

        return [new Vendor(self::GOOGLE_TAG_GATEWAY, Category::MARKETING, [$host . $path, $path])];
    }

    /**
     * The custom vendors block their patterns both as sources and as inline scripts, like the 2.x lists.
     *
     * @return list<Vendor>
     */
    private function customVendors(): array
    {
        $vendors = [];
        foreach ($this->options->records('custom_vendors') as $record) {
            $vendor = $this->customVendor($record);
            if ($vendor === null) {
                continue;
            }

            $vendors[] = $vendor;
        }

        return $vendors;
    }

    /**
     * @param array<array-key, mixed> $record
     */
    private function customVendor(array $record): ?Vendor
    {
        $patterns = \array_values(\array_filter(
            \is_array($record['patterns'] ?? null) ? $record['patterns'] : [],
            \is_string(...)
        ));

        try {
            return new Vendor(
                \is_string($record['id'] ?? null) ? $record['id'] : '',
                \is_string($record['category'] ?? null) ? $record['category'] : '',
                $patterns,
                $patterns
            );
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
