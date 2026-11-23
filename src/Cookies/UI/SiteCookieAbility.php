<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cookies\UI;

use ItalyCookieChoices\Cookies\Application\SiteCookies;
use ItalyCookieChoices\Settings\UI\Abilities;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The ability italy-cookie-choices/add-site-cookie: a cookie of the site itself, for the cookie table.
 */
final readonly class SiteCookieAbility implements SubscriberInterface
{
    private const ERROR = 'italy_cookie_choices_invalid_cookie';

    public function __construct(
        private Abilities $abilities,
        private SiteCookies $cookies,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_abilities_api_init' => 'register';
    }

    public function register(): void
    {
        $properties = [
            'name' => ['type' => 'string', 'minLength' => 1, 'description' => 'Add * for a prefix, like _ga_*.'],
            'category' => ['type' => 'string', 'pattern' => '^[a-z0-9_-]+$'],
            'duration' => ['type' => 'string', 'description' => 'How long it is kept, like "1 year" or "Session".'],
            'purpose' => ['type' => 'string'],
        ];

        $this->abilities->register('add-site-cookie', [
            'label' => \__('Add a site cookie', 'italy-cookie-choices'),
            'description' => \implode(' ', [
                \__(
                    'Adds a cookie of the site itself to the cookie table, like the login of WordPress or the cart.',
                    'italy-cookie-choices'
                ),
                \__('Its category is necessary, preferences, statistics or marketing.', 'italy-cookie-choices'),
            ]),
            'input_schema' => [
                'type' => 'object',
                'properties' => $properties,
                'required' => ['name', 'category', 'duration', 'purpose'],
                'additionalProperties' => false,
            ],
            'output_schema' => ['type' => 'object', 'properties' => $properties],
            'execute_callback' => $this->add(...),
        ], ['readonly' => false, 'destructive' => false, 'idempotent' => false]);
    }

    /**
     * @param array{name: string, category: string, duration: string, purpose: string} $input
     * @return array<array-key, mixed>|\WP_Error
     */
    private function add(array $input): array|\WP_Error
    {
        try {
            return $this->cookies->add($input['name'], $input['category'], $input['duration'], $input['purpose']);
        } catch (\InvalidArgumentException $exception) {
            return $this->abilities->invalidInput(self::ERROR, $exception);
        }
    }
}
