<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Settings\SettingsUpdate;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The abilities of the settings, in the shape of the Schema:
 *
 * * italy-cookie-choices/get-settings returns the saved settings;
 * * italy-cookie-choices/update-settings changes some of them, checked like on the settings page.
 */
final readonly class SettingsAbility implements SubscriberInterface
{
    private const ERROR = 'italy_cookie_choices_invalid_settings';

    public function __construct(
        private Abilities $abilities,
        private Options $options,
        private Schema $schema,
        private SettingsUpdate $update,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_abilities_api_init' => 'register';
    }

    public function register(): void
    {
        $schema = $this->schema->toJsonSchema();

        $this->abilities->register('get-settings', [
            'label' => \__('Get the cookie settings', 'italy-cookie-choices'),
            'description' => \__(
                'Returns the settings of the cookie banner, of the blocking of the vendors and of the consent log.',
                'italy-cookie-choices'
            ),
            'output_schema' => $schema,
            'execute_callback' => $this->settings(...),
        ], ['readonly' => true]);

        $this->abilities->register('update-settings', [
            'label' => \__('Update the cookie settings', 'italy-cookie-choices'),
            'description' => \implode(' ', [
                \__(
                    'Changes only the settings given, with the names of get-settings, and returns the saved settings.',
                    'italy-cookie-choices'
                ),
                \__(
                    'A checkbox is on with 1, null removes a setting, like a checkbox turned off.',
                    'italy-cookie-choices'
                ),
                \__('A list replaces the saved list, the values not valid are not saved.', 'italy-cookie-choices'),
            ]),
            'input_schema' => [
                ...$schema,
                'properties' => \array_map(
                    static fn(array $property): array => ['anyOf' => [$property, ['type' => 'null']]],
                    $schema['properties']
                ),
                'minProperties' => 1,
            ],
            'output_schema' => $schema,
            'execute_callback' => $this->updated(...),
        ], ['readonly' => false, 'destructive' => true, 'idempotent' => true]);
    }

    /**
     * Only the keys of the Schema, an option saved without the Sanitizer can have others.
     *
     * @return array<array-key, mixed>
     */
    private function settings(): array
    {
        return \array_intersect_key($this->options->all(), $this->schema->toJsonSchema()['properties']);
    }

    /**
     * @param array<string, mixed> $patch
     * @return array<array-key, mixed>|\WP_Error
     */
    private function updated(array $patch): array|\WP_Error
    {
        try {
            $this->update->apply($patch);
        } catch (\InvalidArgumentException $exception) {
            return $this->abilities->invalidInput(self::ERROR, $exception);
        }

        return $this->settings();
    }
}
