<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support;

/**
 * The abilities of the plugin, run with LogsIn as a user of a role.
 */
trait RunsAbilities
{
    private function ability(string $name): \WP_Ability
    {
        $ability = \wp_get_ability($name);
        $this->assertInstanceOf(\WP_Ability::class, $ability);

        return $ability;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function annotations(\WP_Ability $ability): array
    {
        $annotations = $ability->get_meta_item('annotations');
        $this->assertIsArray($annotations);

        return $annotations;
    }
}
