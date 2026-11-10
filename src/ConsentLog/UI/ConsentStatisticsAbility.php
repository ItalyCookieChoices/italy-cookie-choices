<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI;

use ItalyCookieChoices\Settings\UI\Abilities;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The ability italy-cookie-choices/get-consent-statistics: the summary of the consent log by month.
 */
final readonly class ConsentStatisticsAbility implements SubscriberInterface
{
    public function __construct(
        private Abilities $abilities,
        private StatisticsReport $report,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_abilities_api_init' => 'register';
    }

    public function register(): void
    {
        $count = ['type' => 'integer'];

        $this->abilities->register('get-consent-statistics', [
            'label' => \__('Get the consent statistics', 'italy-cookie-choices'),
            'description' => \__(
                'Counts the answers to the cookie banner by month, from the consent log, the newest month first.',
                'italy-cookie-choices'
            ),
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'categories' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => ['id' => ['type' => 'string'], 'label' => ['type' => 'string']],
                        ],
                    ],
                    'months' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'month' => ['type' => 'string', 'description' => 'The month, like 2026-11.'],
                                'choices' => $count,
                                'acceptAll' => $count,
                                'rejectAll' => $count,
                                'partial' => $count,
                                'withdrawals' => $count,
                                'categories' => ['type' => 'object', 'additionalProperties' => $count],
                            ],
                        ],
                    ],
                ],
            ],
            'execute_callback' => $this->report->toArray(...),
        ], ['readonly' => true]);
    }
}
