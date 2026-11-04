<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Domain;

/**
 * Where the consent of the current visitor is kept.
 */
interface ConsentStorage
{
    /**
     * Keeps the stored consent as it is until the date, nothing when there is none.
     */
    public function renew(\DateTimeImmutable $expiresAt): void;
}
