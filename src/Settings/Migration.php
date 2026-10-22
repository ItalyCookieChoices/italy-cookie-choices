<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

use ItalyStrap\Event\SubscriberInterface;

/**
 * Brings the stored option to the current schema version, one step at a time.
 *
 * It runs before the option is registered, so the values are stored as they are.
 */
final readonly class Migration implements SubscriberInterface
{
    private const IMPLICIT_CONSENTS = ['scroll', 'secondView'];
    private const ALWAYS_ON = ['block', 'widget_block'];
    private const SNIPPET_LISTS = [
        'custom_script_block' => 'block_script',
        'custom_script_block_body_exclude' => 'allow_script',
    ];

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

        foreach ($this->steps() as $version => $step) {
            if ($version <= $storedVersion) {
                continue;
            }

            $step();
        }

        \update_option(Schema::VERSION_OPTION, Schema::VERSION);
    }

    /**
     * @return array<int, callable(): void>
     */
    private function steps(): array
    {
        return [
            1 => $this->createTheDefaults(...),
            2 => $this->removeTheImplicitConsents(...),
            3 => $this->moveTheSnippetsToTheLists(...),
        ];
    }

    /**
     * Version 1: the 2.x values are kept, a new installation gets the defaults.
     */
    private function createTheDefaults(): void
    {
        if (\get_option(Schema::OPTION, null) !== null) {
            return;
        }

        \add_option(Schema::OPTION, $this->schema->defaults());
    }

    /**
     * Version 2: accepting on scroll and on the second page view is not a valid consent under the GDPR.
     */
    private function removeTheImplicitConsents(): void
    {
        $option = \get_option(Schema::OPTION, null);
        if (!\is_array($option)) {
            return;
        }

        $migrated = \array_diff_key($option, \array_flip(self::IMPLICIT_CONSENTS));
        if ($migrated === $option) {
            return;
        }

        \update_option(Schema::OPTION, $migrated);
    }

    /**
     * Version 3: the vendors are always blocked in the content and the widgets,
     * the snippets of the 2.x textareas become texts in the lists of the blocked and allowed scripts.
     */
    private function moveTheSnippetsToTheLists(): void
    {
        $option = \get_option(Schema::OPTION, null);
        if (!\is_array($option)) {
            return;
        }

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

        if ($migrated === $option) {
            return;
        }

        \update_option(Schema::OPTION, $migrated);
    }

    private function storedVersion(): int
    {
        $version = \get_option(Schema::VERSION_OPTION, 0);

        return \is_numeric($version) ? (int) $version : 0;
    }
}
