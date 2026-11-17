<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Domain;

/**
 * The scan that runs by itself: how often, on which pages, and whether the new findings are emailed.
 */
final readonly class Schedule
{
    public function __construct(
        public Frequency $frequency = Frequency::Off,
        public Selection $selection = new Selection(),
        public bool $email = false,
    ) {
    }
}
