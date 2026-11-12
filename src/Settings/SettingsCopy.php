<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

/**
 * Copies the settings of a site of the network to other sites, once: no link between the sites is stored.
 *
 * Each target keeps the settings of its own content and domain, its texts when they are not copied,
 * and its consent log, in its own table.
 */
final readonly class SettingsCopy
{
    public function __construct(
        private NetworkSettings $network,
        private Migration $migration,
        private Schema $schema,
    ) {
    }

    /**
     * @param list<int> $targets The sites that get the settings, the source and the missing sites are skipped.
     * @param bool $withTexts Whether the texts are copied too, or each target keeps its own, for its language.
     * @return list<int> The sites that got the settings.
     * @throws \InvalidArgumentException When the sites use the main site, or there is nothing to copy.
     */
    public function copy(int $source, array $targets, bool $withTexts): array
    {
        if ($this->network->sharesTheMainSite()) {
            throw new \InvalidArgumentException(
                \__('The sites use the settings of the main site.', 'italy-cookie-choices')
            );
        }

        $targets = $this->existing($source, $targets);
        if (!$this->exists($source) || $targets === []) {
            throw new \InvalidArgumentException(
                \__('Choose a site to copy from and at least another site to copy to.', 'italy-cookie-choices')
            );
        }

        $kept = \array_flip($withTexts ? Schema::SITE_CONTENT : [...Schema::SITE_CONTENT, ...Schema::SITE_TEXTS]);
        $settings = \array_diff_key($this->settingsOf($source), $kept);
        foreach ($targets as $target) {
            $this->save($target, $settings, $kept);
        }

        return $targets;
    }

    /**
     * @param list<int> $targets
     * @return list<int>
     */
    private function existing(int $source, array $targets): array
    {
        return \array_values(\array_filter(
            \array_unique($targets),
            fn(int $target): bool => $target !== $source && $this->exists($target)
        ));
    }

    private function exists(int $siteId): bool
    {
        return \get_site($siteId) instanceof \WP_Site;
    }

    /**
     * The settings of the source, migrated when the site was not opened since an update,
     * the defaults when it has none.
     *
     * @return array<array-key, mixed>
     */
    private function settingsOf(int $source): array
    {
        \switch_to_blog($source);
        $option = \get_option(Schema::OPTION, null);
        $version = \get_option(Schema::VERSION_OPTION, 0);
        \restore_current_blog();

        if (!\is_array($option)) {
            return $this->schema->defaults();
        }

        return $this->migration->upgrade($option, \is_numeric($version) ? (int) $version : 0);
    }

    /**
     * @param array<array-key, mixed> $settings
     * @param array<array-key, int> $kept The keys the target keeps.
     */
    private function save(int $target, array $settings, array $kept): void
    {
        \switch_to_blog($target);
        $option = \get_option(Schema::OPTION);
        $own = \array_intersect_key(\is_array($option) ? $option : [], $kept);
        \update_option(Schema::OPTION, [...$settings, ...$own]);
        \update_option(Schema::VERSION_OPTION, Schema::VERSION);
        \restore_current_blog();
    }
}
