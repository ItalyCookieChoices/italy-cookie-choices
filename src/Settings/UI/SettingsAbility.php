<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The ability italy-cookie-choices/get-settings: the saved settings, in the shape of the Schema.
 */
final readonly class SettingsAbility implements SubscriberInterface
{
    public function __construct(
        private Abilities $abilities,
        private Options $options,
        private Schema $schema,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_abilities_api_init' => 'register';
    }

    public function register(): void
    {
        $this->abilities->register('get-settings', [
            'label' => \__('Get the cookie settings', 'italy-cookie-choices'),
            'description' => \__(
                'Returns the settings of the cookie banner, of the blocking of the vendors and of the consent log.',
                'italy-cookie-choices'
            ),
            'output_schema' => $this->schema->toJsonSchema(),
            'execute_callback' => $this->settings(...),
        ], ['readonly' => true]);
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
}
