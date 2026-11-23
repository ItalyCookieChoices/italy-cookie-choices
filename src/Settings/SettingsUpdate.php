<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

/**
 * A partial update of the settings, like a JSON merge patch: the names given replace the saved values,
 * null removes one, like a checkbox turned off. The Sanitizer of the option checks the result,
 * like for the settings page.
 */
final readonly class SettingsUpdate
{
    public function __construct(
        private Options $options,
        private NetworkSettings $network,
    ) {
    }

    /**
     * @param array<string, mixed> $patch
     * @return array<array-key, mixed> The saved settings.
     * @throws \InvalidArgumentException When the site uses the settings of the main site and the patch is not
     *     only of its texts.
     */
    public function apply(array $patch): array
    {
        $this->assertTheSiteOwns($patch);
        $settings = \array_filter(
            [...$this->options->all(), ...$patch],
            static fn(mixed $value): bool => $value !== null
        );
        $this->options->replace($settings);

        return $this->options->all();
    }

    /**
     * @param array<string, mixed> $patch
     */
    private function assertTheSiteOwns(array $patch): void
    {
        if (!$this->network->usesTheMainSite()) {
            return;
        }

        $others = \array_diff(\array_keys($patch), Schema::SITE_TEXTS);
        if ($others === []) {
            return;
        }

        throw new \InvalidArgumentException(\sprintf(
            /* translators: 1: the names of the settings, 2: the names of the texts of the site, comma separated. */
            \__(
                'This site uses the main site settings, only its texts change, not: %1$s. Texts: %2$s.',
                'italy-cookie-choices'
            ),
            \implode(', ', $others),
            \implode(', ', Schema::SITE_TEXTS)
        ));
    }
}
