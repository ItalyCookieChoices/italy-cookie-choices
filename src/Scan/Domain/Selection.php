<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Domain;

/**
 * What the admin chose to scan: the home page, the post types, the categories and single posts or pages.
 */
final readonly class Selection
{
    /**
     * @param list<string> $postTypes
     * @param list<int> $categories
     * @param list<int> $posts
     */
    public function __construct(
        public bool $home = false,
        public array $postTypes = [],
        public array $categories = [],
        public array $posts = [],
    ) {
    }
}
