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
