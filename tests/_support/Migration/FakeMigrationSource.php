<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Migration;

use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Domain\MigrationSource;

final class FakeMigrationSource implements MigrationSource
{
    public function __construct(
        private readonly string $id,
        private readonly ImportedSettings $imported,
        private readonly bool $found = true,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return \ucfirst($this->id);
    }

    public function isFound(): bool
    {
        return $this->found;
    }

    public function read(): ImportedSettings
    {
        return $this->imported;
    }
}
