<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Infrastructure;

use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Blocking\Domain\Vendors;

/**
 * Blocks the scripts and the frames of the vendors in a piece of HTML, the browser runs them after the consent.
 *
 * A blocked script gets type="text/plain" and data-category, a blocked frame moves its src to data-icc-src.
 * Both get data-icc-vendor when a vendor matches them, for the consent per vendor.
 * Authors block an element by hand with data-category, it wins over the vendors.
 * Running it twice on the same HTML changes nothing more.
 */
final readonly class HtmlBlocker
{
    public const CATEGORY = 'data-category';
    public const SOURCE = 'data-icc-src';
    public const TYPE = 'data-icc-type';
    public const VENDOR = 'data-icc-vendor';

    private const BLOCKED_TYPE = 'text/plain';
    private const JAVASCRIPT_TYPES = [
        '',
        'text/javascript',
        'application/javascript',
        'text/ecmascript',
        'application/ecmascript',
        'module',
    ];
    private const FRAMES = ['IFRAME', 'EMBED'];

    /**
     * The scripts printed by the plugin, never blocked.
     */
    private const OWN_SCRIPT_ID = 'italy-cookie-choices-';

    /**
     * @param string $category The category of every script and frame, instead of the vendors.
     * @param string $vendor The vendor of the scripts that match no vendor, like the inline scripts of a tag.
     */
    public function block(string $html, Vendors $vendors, string $category = '', string $vendor = ''): string
    {
        if (\preg_match('/<(script|iframe|embed)\b/i', $html) !== 1) {
            return $html;
        }

        $processor = new \WP_HTML_Tag_Processor($html);
        while ($processor->next_tag()) {
            $tag = $processor->get_tag();
            if ($tag === 'SCRIPT') {
                $this->blockScript($processor, $vendors, $category, $vendor);
                continue;
            }

            if (\in_array($tag, self::FRAMES, true)) {
                $this->blockFrame($processor, $vendors, $category);
            }
        }

        return $processor->get_updated_html();
    }

    private function blockScript(
        \WP_HTML_Tag_Processor $processor,
        Vendors $vendors,
        string $category,
        string $vendor
    ): void {
        $type = \strtolower(\trim($this->attribute($processor, 'type')));
        if (!\in_array($type, self::JAVASCRIPT_TYPES, true) || $this->isOwnScript($processor)) {
            return;
        }

        $match = $this->scriptVendor($processor, $vendors);
        $category = $this->categoryOf($processor, $category) ?? $match?->category();
        if ($category === null) {
            return;
        }

        $this->setVendor($processor, $match?->id() ?? $vendor);
        $processor->set_attribute('type', self::BLOCKED_TYPE);
        $processor->set_attribute(self::CATEGORY, $category);
        if ($type === 'module') {
            $processor->set_attribute(self::TYPE, $type);
        }
    }

    private function blockFrame(\WP_HTML_Tag_Processor $processor, Vendors $vendors, string $category): void
    {
        $source = $this->attribute($processor, 'src');
        if ($source === '') {
            return;
        }

        $match = $vendors->loading($source);
        $category = $this->categoryOf($processor, $category) ?? $match?->category();
        if ($category === null) {
            return;
        }

        $this->setVendor($processor, $match?->id() ?? '');
        $processor->set_attribute(self::SOURCE, $source);
        $processor->remove_attribute('src');
        $processor->set_attribute(self::CATEGORY, $category);
    }

    private function scriptVendor(\WP_HTML_Tag_Processor $processor, Vendors $vendors): ?Vendor
    {
        $source = $this->attribute($processor, 'src');

        return $source !== ''
            ? $vendors->loading($source)
            : $vendors->running($processor->get_modifiable_text());
    }

    private function setVendor(\WP_HTML_Tag_Processor $processor, string $vendor): void
    {
        if ($vendor === '' || $this->attribute($processor, self::VENDOR) !== '') {
            return;
        }

        $processor->set_attribute(self::VENDOR, $vendor);
    }

    /**
     * The category set by the author, or the one asked by the caller.
     */
    private function categoryOf(\WP_HTML_Tag_Processor $processor, string $category): ?string
    {
        $authorCategory = \trim($this->attribute($processor, self::CATEGORY));
        if ($authorCategory !== '') {
            return $authorCategory;
        }

        return $category !== '' ? $category : null;
    }

    private function isOwnScript(\WP_HTML_Tag_Processor $processor): bool
    {
        return \str_starts_with($this->attribute($processor, 'id'), self::OWN_SCRIPT_ID);
    }

    /**
     * The attribute value, an empty string when it is missing or has no value.
     */
    private function attribute(\WP_HTML_Tag_Processor $processor, string $name): string
    {
        $value = $processor->get_attribute($name);

        return \is_string($value) ? $value : '';
    }
}
