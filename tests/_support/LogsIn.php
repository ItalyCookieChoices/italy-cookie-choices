<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support;

/**
 * Logs in a new user of a role, an administrator is a super admin on a network, so it has unfiltered_html.
 */
trait LogsIn
{
    private function logInAs(string $role): void
    {
        $userId = self::factory()->user->create(['role' => $role]);
        $this->assertIsInt($userId);
        if ($role === 'administrator' && \is_multisite()) {
            \grant_super_admin($userId);
        }

        \wp_set_current_user($userId);
    }
}
