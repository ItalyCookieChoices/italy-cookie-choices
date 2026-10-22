<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Domain;

interface ConsentRepository
{
    /**
     * The consent of the current visitor, null when there is none or it cannot be read.
     */
    public function current(): ?Consent;
}
