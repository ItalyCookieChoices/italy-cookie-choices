<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

use ItalyCookieChoices\Blocking\Domain\Vendors;
use ItalyCookieChoices\Blocking\Infrastructure\HtmlBlocker;
use ItalyCookieChoices\Scan\Domain\Findings;

/**
 * Finds in the HTML of a page the vendors of its scripts, frames and tracking pixels, with the patterns
 * the blocker uses, and the hosts of the scripts and frames of the third parties no vendor covers.
 *
 * It reads the page as the visitors get it before a choice: the scripts blocked by the plugin keep their src,
 * the frames move it to data-icc-src. The cookies set by JavaScript are not seen, they come from the vendors.
 */
final readonly class PageAnalyzer
{
    private const FRAMES = ['IFRAME', 'EMBED'];
    private const PIXELS = ['IMG'];
    /** The hosts of the Google Fonts, the style sheets and the font files. */
    private const GOOGLE_FONTS_HOSTS = ['fonts.googleapis.com', 'fonts.gstatic.com'];
    /** The links that load a style sheet or a font. */
    private const LOADING_LINKS = ['stylesheet', 'preload'];

    public function analyze(string $html, Vendors $vendors, string $siteHost): Findings
    {
        $found = [];
        $hosts = [];
        $googleFonts = false;
        $processor = new \WP_HTML_Tag_Processor($html);
        while ($processor->next_tag()) {
            $tag = (string) $processor->get_tag();
            $googleFonts = $googleFonts || $this->loadsGoogleFonts($processor, $tag);
            $source = $this->sourceOf($processor, $tag);
            $vendor = $source === ''
                ? $this->inlineVendor($processor, $tag, $vendors)
                : $vendors->loading($source)?->id();
            if ($vendor !== null) {
                $found[$vendor] = true;
                continue;
            }

            $host = $this->thirdPartyHost($source, $siteHost);
            if ($host !== '' && !\in_array($tag, self::PIXELS, true)) {
                $hosts[$host] = true;
            }
        }

        return new Findings(\array_keys($found), \array_keys($hosts), $googleFonts);
    }

    /**
     * A style sheet or a font linked from Google, or a style that imports them.
     */
    private function loadsGoogleFonts(\WP_HTML_Tag_Processor $processor, string $tag): bool
    {
        if ($tag === 'STYLE') {
            return $this->mentionsGoogleFonts($processor->get_modifiable_text());
        }

        if ($tag !== 'LINK') {
            return false;
        }

        $rel = \explode(' ', \strtolower($this->attribute($processor, 'rel')));

        return \array_intersect($rel, self::LOADING_LINKS) !== []
            && $this->mentionsGoogleFonts($this->attribute($processor, 'href'));
    }

    private function mentionsGoogleFonts(string $text): bool
    {
        foreach (self::GOOGLE_FONTS_HOSTS as $host) {
            if (\str_contains($text, $host)) {
                return true;
            }
        }

        return false;
    }

    private function sourceOf(\WP_HTML_Tag_Processor $processor, string $tag): string
    {
        if ($tag === 'SCRIPT' || \in_array($tag, self::PIXELS, true)) {
            return $this->attribute($processor, 'src');
        }

        if (!\in_array($tag, self::FRAMES, true)) {
            return '';
        }

        $source = $this->attribute($processor, 'src');

        return $source !== '' ? $source : $this->attribute($processor, HtmlBlocker::SOURCE);
    }

    private function inlineVendor(\WP_HTML_Tag_Processor $processor, string $tag, Vendors $vendors): ?string
    {
        return $tag === 'SCRIPT' ? $vendors->running($processor->get_modifiable_text())?->id() : null;
    }

    /**
     * The host of an address outside the site and its subdomains, empty for the site and the relative addresses.
     */
    private function thirdPartyHost(string $source, string $siteHost): string
    {
        $host = \strtolower((string) \wp_parse_url(
            \str_starts_with($source, '//') ? 'https:' . $source : $source,
            \PHP_URL_HOST
        ));
        $siteHost = \strtolower((string) \preg_replace('/^www\./i', '', $siteHost));

        if ($host === '' || $host === $siteHost || \str_ends_with($host, '.' . $siteHost)) {
            return '';
        }

        return $host;
    }

    private function attribute(\WP_HTML_Tag_Processor $processor, string $name): string
    {
        $value = $processor->get_attribute($name);

        return \is_string($value) ? \trim($value) : '';
    }
}
