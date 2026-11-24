<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Application;

use ItalyCookieChoices\Cookies\Application\CookieDurations;

/**
 * How long a cookie of another plugin is kept, as the cookie policy writes it, from its lifetime in seconds:
 * the minutes under a day, the days under 90, then the years when they are whole, the months otherwise.
 */
final readonly class CookieLifetime
{
    public const MINUTE = 60;
    public const DAY = 86400;
    public const MONTH = 2629800;
    public const YEAR = 31536000;

    private const DAYS_LIMIT = 90;
    /** The days a lifetime can be away from whole years, like the 400 days of some cookies. */
    private const YEAR_TOLERANCE = 7;

    public function __construct(
        private CookieDurations $durations,
    ) {
    }

    /**
     * @param int|null $seconds Null for a session cookie.
     */
    public function text(?int $seconds): string
    {
        if ($seconds === null) {
            return $this->durations->session();
        }

        if ($seconds <= 0) {
            return '';
        }

        if ($seconds < self::DAY) {
            return $this->durations->minutes(\max(1, \intdiv($seconds, self::MINUTE)));
        }

        $days = (int) \round($seconds / self::DAY);
        if ($days < self::DAYS_LIMIT) {
            return $this->durations->days($days);
        }

        $years = (int) \round($seconds / self::YEAR);
        if (\abs($days - $years * 365) <= self::YEAR_TOLERANCE) {
            return $this->durations->years($years);
        }

        return $this->durations->months((int) \round($seconds / self::MONTH));
    }
}
