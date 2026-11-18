<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Infrastructure;

use ItalyCookieChoices\Multilingual\Application\ConnectedSites;

/**
 * The connected sites of a multilingual plugin, used only while the plugin is active.
 */
interface PluginConnectedSites extends ConnectedSites
{
    public function isActive(): bool;
}
