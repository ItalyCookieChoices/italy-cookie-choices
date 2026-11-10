<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\UI;

use ItalyCookieChoices\Blocking\Application\VendorSettings;
use ItalyCookieChoices\Settings\UI\Abilities;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The abilities of the vendors: list them, turn a common vendor on or off, add a custom vendor.
 */
final readonly class VendorAbilities implements SubscriberInterface
{
    private const ERROR = 'italy_cookie_choices_invalid_vendor';

    private const ID = ['type' => 'string', 'pattern' => '^[a-z0-9_-]+$'];

    public function __construct(
        private Abilities $abilities,
        private VendorSettings $vendors,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_abilities_api_init' => 'register';
    }

    public function register(): void
    {
        $this->abilities->register('list-vendors', [
            'label' => \__('List the vendors', 'italy-cookie-choices'),
            'description' => \__(
                'Lists the vendors, the consent category they wait for and whether their scripts are blocked.',
                'italy-cookie-choices'
            ),
            'output_schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => self::ID,
                        'name' => ['type' => 'string'],
                        'category' => self::ID,
                        'preset' => ['type' => 'boolean', 'description' => 'True for a common vendor.'],
                        'enabled' => ['type' => 'boolean', 'description' => 'True when it is blocked.'],
                    ],
                ],
            ],
            'execute_callback' => $this->vendors->all(...),
        ], ['readonly' => true]);

        $this->abilities->register('switch-vendor', [
            'label' => \__('Turn a common vendor on or off', 'italy-cookie-choices'),
            'description' => \__(
                'Turns the blocking of a common vendor on or off, by its id from list-vendors.',
                'italy-cookie-choices'
            ),
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => self::ID,
                    'enabled' => ['type' => 'boolean'],
                ],
                'required' => ['id', 'enabled'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => self::ID,
                    'enabled' => ['type' => 'boolean'],
                ],
            ],
            'execute_callback' => $this->switch(...),
        ], ['readonly' => false, 'destructive' => false, 'idempotent' => true]);

        $this->abilities->register('add-custom-vendor', [
            'label' => \__('Add a custom vendor', 'italy-cookie-choices'),
            'description' => \__(
                'Adds a vendor blocked until the consent to its category, by texts of its addresses or scripts.',
                'italy-cookie-choices'
            ),
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string', 'minLength' => 1],
                    'category' => self::ID,
                    'patterns' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1],
                ],
                'required' => ['name', 'category', 'patterns'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => self::ID,
                    'name' => ['type' => 'string'],
                    'category' => self::ID,
                    'patterns' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'cookies' => ['type' => 'array'],
                ],
            ],
            'execute_callback' => $this->add(...),
        ], ['readonly' => false, 'destructive' => false, 'idempotent' => false]);
    }

    /**
     * @param array{id: string, enabled: bool} $input
     * @return array{id: string, enabled: bool}|\WP_Error
     */
    private function switch(array $input): array|\WP_Error
    {
        try {
            $input['enabled'] ? $this->vendors->enable($input['id']) : $this->vendors->disable($input['id']);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception);
        }

        return ['id' => $input['id'], 'enabled' => $input['enabled']];
    }

    /**
     * @param array{name: string, category: string, patterns: list<string>} $input
     * @return array<array-key, mixed>|\WP_Error
     */
    private function add(array $input): array|\WP_Error
    {
        try {
            return $this->vendors->add($input['name'], $input['category'], $input['patterns']);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception);
        }
    }

    private function error(\InvalidArgumentException $exception): \WP_Error
    {
        return new \WP_Error(self::ERROR, $exception->getMessage(), ['status' => 400]);
    }
}
