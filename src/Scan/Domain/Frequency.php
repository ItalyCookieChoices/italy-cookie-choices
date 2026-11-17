<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Domain;

/**
 * How often the scheduled scan runs, as a recurrence of WP-Cron.
 */
enum Frequency: string
{
    case Off = 'off';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    /**
     * The recurrence monthly is added by the plugin to the schedules of WP-Cron, the others are of WordPress.
     */
    public const MONTHLY_RECURRENCE = 'italy_cookie_choices_monthly';
    /**
     * 30 days, in seconds.
     */
    public const MONTHLY_INTERVAL = 2_592_000;

    /**
     * The recurrence of WP-Cron, empty when the scan does not run by itself.
     */
    public function recurrence(): string
    {
        return match ($this) { // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts -- An enum case.
            self::Off => '',
            self::Daily => 'daily',
            self::Weekly => 'weekly',
            self::Monthly => self::MONTHLY_RECURRENCE,
        };
    }
}
