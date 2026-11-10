<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyStrap\Event\SubscriberInterface;

/**
 * The abilities of the plugin for the Abilities API of WordPress 6.9, used by the AI tools and the automations.
 *
 * They are only for the administrators, in the REST API and in the MCP Adapter.
 * On an older WordPress the hooks of the Abilities API never run and nothing is registered.
 */
final readonly class Abilities implements SubscriberInterface
{
    public const CATEGORY = 'italy-cookie-choices';

    private const CAPABILITY = 'manage_options';

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_abilities_api_categories_init' => 'registerCategory';
    }

    public function registerCategory(): void
    {
        \wp_register_ability_category(self::CATEGORY, [
            'label' => \__('Cookie Choices', 'italy-cookie-choices'),
            'description' => \__(
                'The cookie banner, the blocked vendors and the consent log of the site.',
                'italy-cookie-choices'
            ),
        ]);
    }

    /**
     * Registers an ability of the plugin, called on wp_abilities_api_init.
     *
     * @param lowercase-string&non-empty-string $name The name after the prefix of the plugin, like "list-vendors".
     * @param array{
     *     label: string,
     *     description: string,
     *     execute_callback: callable,
     *     input_schema?: array<string, mixed>,
     *     output_schema?: array<string, mixed>
     * } $args
     * @param array{readonly: bool, destructive?: bool, idempotent?: bool} $annotations
     */
    public function register(string $name, array $args, array $annotations): void
    {
        \wp_register_ability(self::CATEGORY . '/' . $name, [
            ...$args,
            'category' => self::CATEGORY,
            'permission_callback' => static fn(): bool => \current_user_can(self::CAPABILITY),
            'meta' => [
                'public' => true,
                'show_in_rest' => true,
                'mcp' => ['public' => true],
                'annotations' => $annotations,
            ],
        ]);
    }
}
