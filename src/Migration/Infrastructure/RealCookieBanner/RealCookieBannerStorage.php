<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\RealCookieBanner;

use ItalyCookieChoices\Migration\Infrastructure\UnregisteredContent;

/**
 * Reads the settings Real Cookie Banner keeps in the database, also when it is deactivated.
 *
 * Each setting is its own option, the texts have an option for each language with WPML or Polylang,
 * like rcb-banner-texts-headline-it. The services, the content blockers and the legal links are posts,
 * the service groups the terms of a taxonomy. Only the structure of its data is read, nothing of it is used.
 *
 * @phpstan-import-type Post from UnregisteredContent
 * @phpstan-type Group array{id: int, name: string, description: string, essential: bool, default: bool, order: int}
 */
final readonly class RealCookieBannerStorage
{
    public const SERVICES = 'rcb-cookie';
    public const BLOCKERS = 'rcb-blocker';
    public const LINKS = 'rcb-banner-link';

    private const VERSION = 'rcb_db_version';
    private const GROUPS = 'rcb-cookie-group';

    public function __construct(
        private UnregisteredContent $content,
    ) {
    }

    public function hasSettings(): bool
    {
        return \get_option(self::VERSION) !== false;
    }

    /**
     * An option of Real Cookie Banner, in the language of the site when it has one for each language.
     */
    public function option(string $name): mixed
    {
        $translated = \get_option($name . '-' . \substr(\get_locale(), 0, 2), null);

        return $translated ?? \get_option($name, null);
    }

    /**
     * The published posts of a type, in their order: the disabled services and blockers are not published.
     *
     * @return list<Post>
     */
    public function posts(string $type): array
    {
        return $this->hasSettings() ? $this->content->posts($type) : [];
    }

    public function meta(int $post, string $key): mixed
    {
        return \get_post_meta($post, $key, true);
    }

    /**
     * The service groups, with the marks of the essential group and of the groups created by the plugin.
     *
     * @return list<Group>
     */
    public function groups(): array
    {
        if (!$this->hasSettings()) {
            return [];
        }

        $groups = [];
        foreach ($this->content->terms(self::GROUPS) as $term) {
            $order = \get_term_meta($term['id'], 'order', true);
            $groups[] = [
                'id' => $term['id'],
                'name' => $term['name'],
                'description' => $term['description'],
                'essential' => \get_term_meta($term['id'], 'isEssential', true) === '1',
                'default' => \get_term_meta($term['id'], 'isDefault', true) === '1',
                'order' => \is_numeric($order) ? (int) $order : 0,
            ];
        }

        return $groups;
    }

    /**
     * The id of the group of a service, 0 without one.
     */
    public function groupOf(int $post): int
    {
        return $this->content->termsOf($post, self::GROUPS)[0] ?? 0;
    }
}
