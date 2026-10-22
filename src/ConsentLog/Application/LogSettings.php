<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\Application;

use ItalyCookieChoices\ConsentLog\Domain\Retention;
use ItalyCookieChoices\Settings\Options;

/**
 * Whether the consents are logged and for how long, from the saved settings.
 */
final readonly class LogSettings
{
    public const ENABLED = 'consent_log';
    public const RETENTION = 'consent_log_retention';

    public function __construct(
        private Options $options,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->options->isActive() && $this->options->isChecked(self::ENABLED);
    }

    public function retention(): Retention
    {
        $months = (int) $this->options->text(self::RETENTION);

        return \in_array($months, Retention::MONTHS, true) ? new Retention($months) : new Retention();
    }
}
