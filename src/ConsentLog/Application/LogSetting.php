<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\Application;

/**
 * The names of the settings of the consent log, in the option of the plugin.
 */
final class LogSetting
{
    public const ACTIVE = 'consent_log';
    /** The months the records are kept. */
    public const RETENTION = 'consent_log_retention';
}
