<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cookies\Application;

/**
 * How long a cookie is kept, as the cookie policy writes it in the language of the site.
 */
final readonly class CookieDurations
{
    public function session(): string
    {
        return \__('Session', 'italy-cookie-choices');
    }

    public function minutes(int $minutes): string
    {
        /* translators: %d: the number of minutes. */
        return \sprintf(\_n('%d minute', '%d minutes', $minutes, 'italy-cookie-choices'), $minutes);
    }

    public function days(int $days): string
    {
        /* translators: %d: the number of days. */
        return \sprintf(\_n('%d day', '%d days', $days, 'italy-cookie-choices'), $days);
    }

    public function months(int $months): string
    {
        /* translators: %d: the number of months. */
        return \sprintf(\_n('%d month', '%d months', $months, 'italy-cookie-choices'), $months);
    }

    public function years(int $years): string
    {
        /* translators: %d: the number of years. */
        return \sprintf(\_n('%d year', '%d years', $years, 'italy-cookie-choices'), $years);
    }
}
