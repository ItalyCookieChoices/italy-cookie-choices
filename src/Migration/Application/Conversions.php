<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Application;

/**
 * The conversions every migration source needs, from the values of another plugin to the ones of the settings.
 */
final readonly class Conversions
{
    /**
     * The durations of the consent the settings offer, in days.
     */
    private const DURATIONS = [30, 90, 182, 365];

    /**
     * The duration of the consent closest to a number of days.
     */
    public function consentDuration(int $days): string
    {
        $closest = self::DURATIONS[0];
        foreach (self::DURATIONS as $duration) {
            if (\abs($duration - $days) < \abs($closest - $days)) {
                $closest = $duration;
            }
        }

        return (string) $closest;
    }

    /**
     * A domain without the scheme, the path and the slashes, like example.com.
     */
    public function cookieDomain(string $domain): string
    {
        $host = \wp_parse_url(\str_contains($domain, '//') ? $domain : '//' . $domain, \PHP_URL_HOST);

        return \is_string($host) ? \strtolower($host) : '';
    }

    /**
     * The slug, the address and the title of a published page, null when it is not one.
     *
     * @return array{slug: string, url: string, title: string}|null
     */
    public function page(int $id): ?array
    {
        $page = $id > 0 ? \get_post($id) : null;
        if (!$page instanceof \WP_Post || $page->post_status !== 'publish') {
            return null;
        }

        return [
            'slug' => $page->post_name,
            'url' => (string) \get_permalink($page),
            'title' => $page->post_title,
        ];
    }

    /**
     * The page of an address of the site, null when the address is not a published page.
     *
     * @return array{slug: string, url: string, title: string}|null
     */
    public function pageOfUrl(string $url): ?array
    {
        return $url === '' ? null : $this->page(\url_to_postid($url));
    }

    /**
     * The id of an image of the media library from its address, empty when it is not one.
     */
    public function attachmentOfUrl(string $url): string
    {
        $id = $url === '' || $url === '#' ? 0 : \attachment_url_to_postid($url);

        return $id > 0 ? (string) $id : '';
    }

    /**
     * A color like #fff or #ffffff, empty when it is not one.
     */
    public function color(mixed $color): string
    {
        return \is_string($color) ? (string) \sanitize_hex_color(\trim($color)) : '';
    }

    /**
     * An id of the settings from a name, like "my-chat" from "My chat".
     */
    public function id(string $name): string
    {
        return \sanitize_title($name);
    }

    /**
     * Custom CSS of another plugin, kept in a comment: its selectors do not match the banner of Cookie Choices.
     */
    public function commentedCss(string $plugin, string $css): string
    {
        return \sprintf(
            "/* %s */\n/*\n%s\n*/",
            \sprintf(
                /* translators: %s: the name of the other plugin. */
                \__('The custom CSS of %s: rewrite it for the classes of Cookie Choices.', 'italy-cookie-choices'),
                $plugin
            ),
            \str_replace('*/', '* /', \trim($css))
        );
    }
}
