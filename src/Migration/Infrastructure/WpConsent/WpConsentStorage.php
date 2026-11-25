<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\WpConsent;

use ItalyCookieChoices\Migration\Infrastructure\UnregisteredContent;

/**
 * Reads the settings WPConsent keeps in the database, also when it is deactivated: its settings option,
 * the categories and their services, terms of its taxonomy, and the cookies, posts in those terms.
 *
 * @phpstan-import-type Term from UnregisteredContent
 * @phpstan-type StoredCookie array{
 *     name: string,
 *     title: string,
 *     description: string,
 *     duration: string,
 *     terms: list<int>
 * }
 */
final readonly class WpConsentStorage
{
    private const SETTINGS = 'wpconsent_settings';
    private const TAXONOMY = 'wpconsent_category';
    private const COOKIES = 'wpconsent_cookie';

    public function __construct(
        private UnregisteredContent $content,
    ) {
    }

    public function hasSettings(): bool
    {
        return \is_array(\get_option(self::SETTINGS));
    }

    /**
     * @return array<array-key, mixed>
     */
    public function settings(): array
    {
        $settings = \get_option(self::SETTINGS);

        return \is_array($settings) ? $settings : [];
    }

    /**
     * The categories, with no parent, and the services, with their category as parent.
     *
     * @return list<Term>
     */
    public function terms(): array
    {
        return $this->hasSettings() ? $this->content->terms(self::TAXONOMY) : [];
    }

    /**
     * @return list<StoredCookie>
     */
    public function cookies(): array
    {
        if (!$this->hasSettings()) {
            return [];
        }

        $cookies = [];
        foreach ($this->content->posts(self::COOKIES) as $post) {
            $cookies[] = [
                'name' => $this->meta($post['id'], 'wpconsent_cookie_id'),
                'title' => $post['title'],
                'description' => $post['content'],
                'duration' => $this->meta($post['id'], 'wpconsent_cookie_duration'),
                'terms' => $this->content->termsOf($post['id'], self::TAXONOMY),
            ];
        }

        return $cookies;
    }

    private function meta(int $post, string $key): string
    {
        $value = \get_post_meta($post, $key, true);

        return \is_scalar($value) ? \trim((string) $value) : '';
    }
}
