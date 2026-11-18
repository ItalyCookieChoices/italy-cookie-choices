<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Infrastructure;

use ItalyCookieChoices\Multilingual\Application\LanguageTranslations;

/**
 * The languages of a multilingual plugin of a single site, used only while the plugin is active.
 */
interface PluginLanguageTranslations extends LanguageTranslations
{
    public function isActive(): bool;
}
