<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

use ItalyCookieChoices\Consent\Domain\Category;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Brings the stored option to the current schema version, one step at a time.
 *
 * It runs before the option is registered, so the values are stored as they are.
 * The imported settings go through the same steps.
 */
final readonly class Migration implements SubscriberInterface
{
    public const CUSTOM_VENDOR = 'custom';

    private const IMPLICIT_CONSENTS = ['scroll', 'secondView'];

    /**
     * The text of the 2.x button that closed the banner, like "Close" or "OK".
     */
    private const LEGACY_BUTTON_TEXT = 'button_text';
    private const ALWAYS_ON = ['block', 'widget_block'];
    private const SNIPPET_LISTS = [
        'custom_script_block' => 'block_script',
        'custom_script_block_body_exclude' => 'allow_script',
    ];
    private const BLOCKED_LISTS = ['block_script', 'block_iframe', 'block_embed'];
    private const ALLOWED_LISTS = ['allow_script', 'allow_iframe', 'allow_embed'];

    public function __construct(
        private Schema $schema,
        private LegacySnippets $snippets,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'plugins_loaded' => 'migrate';
    }

    public function migrate(): void
    {
        $storedVersion = $this->storedVersion();
        if ($storedVersion >= Schema::VERSION) {
            return;
        }

        $this->migrateTheOption($storedVersion);
        \update_option(Schema::VERSION_OPTION, Schema::VERSION);
    }

    /**
     * The option brought from a schema version to the current one.
     *
     * @param array<array-key, mixed> $option
     * @return array<array-key, mixed>
     */
    public function upgrade(array $option, int $fromVersion): array
    {
        foreach ($this->steps() as $version => $step) {
            if ($version <= $fromVersion) {
                continue;
            }

            $option = $step($option);
        }

        return $option;
    }

    /**
     * Version 1: the 2.x values are kept, a new installation gets the defaults.
     */
    private function migrateTheOption(int $storedVersion): void
    {
        $option = \get_option(Schema::OPTION, null);
        if ($option === null && $storedVersion < 1) {
            \add_option(Schema::OPTION, $this->schema->defaults());
            return;
        }

        if (!\is_array($option)) {
            return;
        }

        $migrated = $this->upgrade($option, \max($storedVersion, 1));
        if ($migrated === $option) {
            return;
        }

        \update_option(Schema::OPTION, $migrated);
    }

    /**
     * @return array<int, callable(array<array-key, mixed>): array<array-key, mixed>>
     */
    private function steps(): array
    {
        return [
            2 => $this->removeTheImplicitConsents(...),
            3 => $this->moveTheSnippetsToTheLists(...),
            4 => $this->turnOnTheConsentLog(...),
            5 => $this->moveTheListsToTheVendors(...),
            6 => $this->turnOnTheConsentMode(...),
        ];
    }

    /**
     * Version 2: accepting on scroll and on the second page view is not a valid consent under the GDPR.
     * The 2.x button closed the banner, its text, like "Close", would name the button that accepts everything:
     * that button gets its default text.
     *
     * @param array<array-key, mixed> $option
     * @return array<array-key, mixed>
     */
    private function removeTheImplicitConsents(array $option): array
    {
        return \array_diff_key($option, \array_flip([...self::IMPLICIT_CONSENTS, self::LEGACY_BUTTON_TEXT]));
    }

    /**
     * Version 3: the vendors are always blocked in the content and the widgets,
     * the snippets of the 2.x textareas become texts in the lists of the blocked and allowed scripts.
     *
     * @param array<array-key, mixed> $option
     * @return array<array-key, mixed>
     */
    private function moveTheSnippetsToTheLists(array $option): array
    {
        $migrated = \array_diff_key($option, \array_flip([...self::ALWAYS_ON, ...\array_keys(self::SNIPPET_LISTS)]));
        foreach (self::SNIPPET_LISTS as $textarea => $list) {
            $snippets = $option[$textarea] ?? '';
            $texts = \is_string($snippets) ? $this->snippets->texts($snippets) : [];
            if ($texts === []) {
                continue;
            }

            $saved = \is_array($migrated[$list] ?? null) ? $migrated[$list] : [];
            $migrated[$list] = $saved + \array_combine($texts, $texts);
        }

        return $migrated;
    }

    /**
     * Version 4: the consent log demonstrates the consents, GDPR art. 7(1), it is on with a 12 months retention.
     *
     * @param array<array-key, mixed> $option
     * @return array<array-key, mixed>
     */
    private function turnOnTheConsentLog(array $option): array
    {
        return $option + Schema::CONSENT_LOG_DEFAULTS;
    }

    /**
     * Version 5: the blocked lists become a custom vendor in the marketing category,
     * the allowed lists become the allowed patterns, the values the REST API cannot read are removed.
     *
     * @param array<array-key, mixed> $option
     * @return array<array-key, mixed>
     */
    private function moveTheListsToTheVendors(array $option): array
    {
        $blocked = $this->listTexts($option, self::BLOCKED_LISTS);
        $allowed = $this->listTexts($option, self::ALLOWED_LISTS);
        $migrated = \array_diff_key($option, \array_flip([...self::BLOCKED_LISTS, ...self::ALLOWED_LISTS]));

        if ($blocked !== []) {
            // The 2.x lists have no category, the marketing one needs the most explicit choice.
            $migrated['custom_vendors'] = [[
                'id' => self::CUSTOM_VENDOR,
                'name' => 'Custom',
                'category' => Category::MARKETING,
                'patterns' => $blocked,
            ]];
        }

        if ($allowed !== []) {
            $migrated['allowed_patterns'] = $allowed;
        }

        return $this->validValues($migrated);
    }

    /**
     * Version 6: the Google tags get the Consent Mode v2 signals, they stay blocked until the consent.
     *
     * @param array<array-key, mixed> $option
     * @return array<array-key, mixed>
     */
    private function turnOnTheConsentMode(array $option): array
    {
        return $option + Schema::CONSENT_MODE_DEFAULTS;
    }

    /**
     * @param array<array-key, mixed> $option
     * @param list<string> $lists
     * @return list<string>
     */
    private function listTexts(array $option, array $lists): array
    {
        $texts = [];
        foreach ($lists as $list) {
            $values = \is_array($option[$list] ?? null) ? $option[$list] : [];
            foreach ($values as $key => $value) {
                $texts = [...$texts, ...$this->entryTexts($key, $value)];
            }
        }

        return \array_values(\array_unique($texts));
    }

    /**
     * The text of an entry of a 2.x list: the value, or the key when the value is empty,
     * like {"googlesyndication": ""} saved by the select of the settings page.
     *
     * @return list<string>
     */
    private function entryTexts(int|string $key, mixed $value): array
    {
        if (!\is_string($value)) {
            return [];
        }

        $text = \trim($value);
        if ($text === '' && \is_string($key)) {
            $text = \trim($key);
        }

        return $text === '' ? [] : [$text];
    }

    /**
     * Only the keys of the schema with a valid value, the REST API reads null for an invalid option.
     *
     * @param array<array-key, mixed> $option
     * @return array<array-key, mixed>
     */
    private function validValues(array $option): array
    {
        $properties = $this->schema->toJsonSchema()['properties'];

        return \array_filter(
            $option,
            static fn(mixed $value, int|string $name): bool => \array_key_exists($name, $properties)
                && \rest_validate_value_from_schema($value, $properties[$name], (string) $name) === true,
            \ARRAY_FILTER_USE_BOTH
        );
    }

    private function storedVersion(): int
    {
        $version = \get_option(Schema::VERSION_OPTION, 0);

        return \is_numeric($version) ? (int) $version : 0;
    }
}
