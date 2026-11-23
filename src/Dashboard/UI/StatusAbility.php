<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Dashboard\UI;

use ItalyCookieChoices\Settings\UI\Abilities;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The ability italy-cookie-choices/get-status: the status of the dashboard widget, what makes the consent valid
 * and what is missing.
 */
final readonly class StatusAbility implements SubscriberInterface
{
    public function __construct(
        private Abilities $abilities,
        private SiteStatus $status,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_abilities_api_init' => 'register';
    }

    public function register(): void
    {
        $text = ['type' => 'string'];

        $this->abilities->register('get-status', [
            'label' => \__('Get the status of the cookie consent', 'italy-cookie-choices'),
            'description' => \implode(' ', [
                \__(
                    'Returns what makes the consent valid, like the banner, the policy link and the vendors.',
                    'italy-cookie-choices'
                ),
                \__('The state warning is what is missing, info is a suggestion.', 'italy-cookie-choices'),
            ]),
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => $text,
                                'label' => $text,
                                'value' => $text,
                                'state' => [
                                    'type' => 'string',
                                    'enum' => [SiteStatus::OK, SiteStatus::WARNING, SiteStatus::INFO],
                                ],
                                'url' => ['type' => 'string', 'description' => 'The tab of the settings page.'],
                            ],
                        ],
                    ],
                ],
            ],
            'execute_callback' => fn(): array => ['items' => $this->status->items()],
        ], ['readonly' => true]);
    }
}
