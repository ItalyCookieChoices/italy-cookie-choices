<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

/**
 * Whether the site owner has used the scan assistant, for the suggested text of the privacy policy.
 */
final readonly class AssistantUsage
{
    public const OPTION = 'italy_cookie_choices_scan_assistant_used';

    public function record(): void
    {
        if ($this->wasUsed()) {
            return;
        }

        \update_option(self::OPTION, \gmdate(\DateTimeInterface::ATOM), false);
    }

    public function wasUsed(): bool
    {
        return \get_option(self::OPTION) !== false;
    }
}
