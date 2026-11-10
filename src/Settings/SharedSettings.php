<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

use ItalyStrap\Event\SubscriberInterface;

/**
 * On a site of a network that uses the main site, the option is the one of the main site
 * with the texts of the site, for its language. The consent log stays in the tables of each site.
 *
 * It runs after the default of register_setting(), so a site without its option gets its default texts.
 */
final readonly class SharedSettings implements SubscriberInterface
{
    private const AFTER_THE_DEFAULT = 20;

    public function __construct(
        private NetworkSettings $network,
        private Schema $schema,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        $merge = [self::CALLBACK => 'merge', self::PRIORITY => self::AFTER_THE_DEFAULT];

        yield 'option_' . Schema::OPTION => $merge;
        yield 'default_option_' . Schema::OPTION => $merge;
    }

    public function merge(mixed $option): mixed
    {
        if (!$this->network->usesTheMainSite()) {
            return $option;
        }

        $texts = \array_flip(Schema::SITE_TEXTS);

        return [
            ...\array_diff_key($this->mainSite(), $texts),
            ...\array_intersect_key(\is_array($option) ? $option : [], $texts),
        ];
    }

    /**
     * Read on the main site, where this filter returns the option as it is.
     *
     * @return array<array-key, mixed>
     */
    private function mainSite(): array
    {
        $option = \get_blog_option(\get_main_site_id(), Schema::OPTION);

        return \is_array($option) ? $option : $this->schema->defaults();
    }
}
