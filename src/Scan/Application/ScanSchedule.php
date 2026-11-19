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
        $frequency = $option[Schedule::FREQUENCY] ?? null;

        return new Schedule(
            Frequency::tryFrom(\is_string($frequency) ? $frequency : '') ?? Frequency::Off,
            new Selection(
                ($option[Selection::HOME] ?? false) === true,
                \array_values(\array_filter($this->listOf($option[Selection::POST_TYPES] ?? null), \is_string(...))),
                \array_values(\array_filter($this->listOf($option[Selection::CATEGORIES] ?? null), \is_int(...))),
                \array_values(\array_filter($this->listOf($option[Selection::POSTS] ?? null), \is_int(...))),
            ),
            ($option[Schedule::EMAIL] ?? false) === true
        );
    }

    public function save(Schedule $schedule): void
    {
        \update_option(self::OPTION, [
            Schedule::FREQUENCY => $schedule->frequency->value,
            Selection::HOME => $schedule->selection->home,
            Selection::POST_TYPES => $schedule->selection->postTypes,
            Selection::CATEGORIES => $schedule->selection->categories,
            Selection::POSTS => $schedule->selection->posts,
            Schedule::EMAIL => $schedule->email,
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
