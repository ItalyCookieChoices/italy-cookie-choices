<?php

/**
 * The part of the API of Multisite Language Switcher the plugin uses, for the static analysis and the tests.
 *
 * The connected sites come from $GLOBALS['icc_msls_blogs'], a map of site id => locale; the translations
 * of a post from the option msls_{id} of its site, a map of locale => post id, like the plugin stores them.
 * Loaded only by the tests that need it.
 *
 * @see \ItalyCookieChoices\Multilingual\Infrastructure\MultisiteLanguageSwitcherSites
 *
 * @phpcs:disable
 */

declare(strict_types=1);

namespace lloc\Msls\Blog {

    if (!\class_exists(Blog::class)) {
        class Blog
        {
            public function __construct(
                public int $userblog_id,
                private string $language,
            ) {
            }

            public function get_language(string $preset = 'en_US'): string
            {
                return $this->language === '' ? $preset : $this->language;
            }
        }
    }

    if (!\class_exists(Collection::class)) {
        class Collection
        {
            /**
             * @return array<int, Blog>
             */
            public function get_objects(): array
            {
                $blogs = \is_array($GLOBALS['icc_msls_blogs'] ?? null) ? $GLOBALS['icc_msls_blogs'] : [];
                $objects = [];
                foreach ($blogs as $siteId => $locale) {
                    $objects[(int) $siteId] = new Blog((int) $siteId, \is_string($locale) ? $locale : '');
                }

                return $objects;
            }
        }
    }
}

namespace lloc\Msls\Options\Post {

    if (!\class_exists(Post::class)) {
        class Post
        {
            /** @var array<array-key, mixed> */
            private array $translations;

            public function __construct(int $id)
            {
                $option = \get_option('msls_' . $id);
                $this->translations = \is_array($option) ? $option : [];
            }

            public function has_value(string $key): bool
            {
                return !empty($this->translations[$key]);
            }

            public function __get(string $key): mixed
            {
                return $this->translations[$key] ?? null;
            }
        }
    }
}

namespace {

    if (!\function_exists('msls_blog_collection')) {
        function msls_blog_collection(): \lloc\Msls\Blog\Collection
        {
            return new \lloc\Msls\Blog\Collection();
        }
    }

    if (!\function_exists('msls_get_post')) {
        function msls_get_post(int $id): \lloc\Msls\Options\Post\Post
        {
            return new \lloc\Msls\Options\Post\Post($id);
        }
    }
}
