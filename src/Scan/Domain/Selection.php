<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Domain;

/**
 * What the admin chose to scan: the home page, the post types, the categories and single posts or pages.
 */
final readonly class Selection
{
    /** The names of the choices, in the REST API, in the saved schedule and in the page data. */
    public const HOME = 'home';
    public const POST_TYPES = 'postTypes';
    public const CATEGORIES = 'categories';
    public const POSTS = 'posts';

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
