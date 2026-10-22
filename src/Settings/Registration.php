<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

use ItalyStrap\Event\SubscriberInterface;

/**
 * Registers the option for the settings page and the REST API, with its defaults and schema.
 */
final readonly class Registration implements SubscriberInterface
{
    public function __construct(
        private Schema $schema,
        private Sanitizer $sanitizer,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'init' => 'register';
    }

    public function register(): void
    {
        \register_setting(
            Schema::GROUP,
            Schema::OPTION,
            [
                'type' => 'object',
                'description' => 'Italy Cookie Choices settings.',
                'default' => $this->schema->defaults(),
                'sanitize_callback' => $this->sanitizer,
                'show_in_rest' => [
                    'schema' => $this->schema->toJsonSchema(),
                ],
            ]
        );
    }
}
