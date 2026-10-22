<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Application;

use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Blocking\Domain\Vendors;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Settings\Options;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Collects the vendors to block from the presets, the saved lists and the listeners of BuildingVendors.
 */
final readonly class VendorsFactory
{
    public const CUSTOM = 'custom';

    private const BLOCKED_LISTS = ['block_script', 'block_iframe', 'block_embed'];
    private const ALLOWED_LISTS = ['allow_script', 'allow_iframe', 'allow_embed'];

    public function __construct(
        private VendorPresets $presets,
        private EventDispatcherInterface $dispatcher,
        private Options $options,
    ) {
    }

    public function create(): Vendors
    {
        $vendors = $this->presets->all();
        $blocked = $this->savedTexts(self::BLOCKED_LISTS);
        if ($blocked !== []) {
            // The 2.x lists have no category, the marketing one needs the most explicit choice.
            $vendors[] = new Vendor(self::CUSTOM, Category::MARKETING, $blocked, $blocked);
        }

        $event = new BuildingVendors($vendors, $this->savedTexts(self::ALLOWED_LISTS));
        $this->dispatcher->dispatch($event);

        return $event->vendors();
    }

    /**
     * @param list<string> $lists
     * @return list<string>
     */
    private function savedTexts(array $lists): array
    {
        $texts = [];
        foreach ($lists as $list) {
            $texts = [...$texts, ...$this->options->values($list)];
        }

        return \array_values(\array_unique($texts));
    }
}
