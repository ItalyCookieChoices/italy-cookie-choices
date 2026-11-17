<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

use ItalyCookieChoices\Scan\Domain\Frequency;
use ItalyCookieChoices\Scan\Domain\Schedule;
use ItalyCookieChoices\Scan\Domain\Selection;

/**
 * The schedule of the scan, in its own option: it is not part of the settings exported and imported.
 */
final readonly class ScanSchedule
{
    public const OPTION = 'italy_cookie_choices_scan_schedule';

    public function get(): Schedule
    {
        $option = \get_option(self::OPTION);
        $option = \is_array($option) ? $option : [];
        $frequency = $option['frequency'] ?? null;

        return new Schedule(
            Frequency::tryFrom(\is_string($frequency) ? $frequency : '') ?? Frequency::Off,
            new Selection(
                ($option['home'] ?? false) === true,
                \array_values(\array_filter($this->listOf($option['postTypes'] ?? null), \is_string(...))),
                \array_values(\array_filter($this->listOf($option['categories'] ?? null), \is_int(...))),
                \array_values(\array_filter($this->listOf($option['posts'] ?? null), \is_int(...))),
            ),
            ($option['email'] ?? false) === true
        );
    }

    public function save(Schedule $schedule): void
    {
        \update_option(self::OPTION, [
            'frequency' => $schedule->frequency->value,
            'home' => $schedule->selection->home,
            'postTypes' => $schedule->selection->postTypes,
            'categories' => $schedule->selection->categories,
            'posts' => $schedule->selection->posts,
            'email' => $schedule->email,
        ], false);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function listOf(mixed $value): array
    {
        return \is_array($value) ? $value : [];
    }
}
