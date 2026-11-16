<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

use ItalyCookieChoices\Scan\Domain\Selection;
use ItalyCookieChoices\Scan\Domain\Targets;

/**
 * The published pages of a selection, up to a limit: the home page first, then the posts, without duplicates.
 */
final readonly class ScanTargets
{
    public const HOME = 0;
    private const LIMIT = 500;
    private const PUBLISHED = 'publish';

    public function __construct(
        private int $limit = self::LIMIT,
    ) {
    }

    public function of(Selection $selection): Targets
    {
        $ids = [
            ...($selection->home ? [self::HOME] : []),
            ...$this->postsOf($selection),
        ];
        $ids = \array_values(\array_unique($ids));

        $targets = \array_map(
            fn(int $id): array => ['id' => $id, 'title' => $this->titleOf($id), 'url' => $this->urlOf($id)],
            \array_slice($ids, 0, $this->limit)
        );

        return new Targets($targets, \count($ids) > $this->limit);
    }

    /**
     * The address of a published page that the visitors can see, empty when there is none.
     */
    public function urlOf(int $id): string
    {
        if ($id === self::HOME) {
            return \home_url('/');
        }

        $post = \get_post($id);

        return $post instanceof \WP_Post && $this->isVisible($post) ? (string) \get_permalink($post) : '';
    }

    private function isVisible(\WP_Post $post): bool
    {
        return $post->post_status === self::PUBLISHED && \is_post_type_viewable($post->post_type);
    }

    private function titleOf(int $id): string
    {
        return $id === self::HOME
            ? \__('Home page', 'italy-cookie-choices')
            : \html_entity_decode(\get_the_title($id), \ENT_QUOTES);
    }

    /**
     * @return list<int>
     */
    private function postsOf(Selection $selection): array
    {
        $postTypes = \array_values(\array_filter($selection->postTypes, \is_post_type_viewable(...)));

        return \array_values(\array_unique([
            ...($postTypes === [] ? [] : $this->query(['post_type' => $postTypes])),
            ...($selection->categories === [] ? [] : $this->query(['category__in' => $selection->categories])),
            ...\array_filter($selection->posts, fn(int $id): bool => $this->urlOf($id) !== ''),
        ]));
    }

    /**
     * @param array{post_type?: list<string>, category__in?: list<int>} $query
     * @return list<int>
     */
    private function query(array $query): array
    {
        $ids = \get_posts([
            ...$query,
            'post_status' => self::PUBLISHED,
            'posts_per_page' => $this->limit + 1,
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => false,
        ]);

        // With 'fields' => 'ids' the posts are their ids.
        return \array_values($ids);
    }
}
