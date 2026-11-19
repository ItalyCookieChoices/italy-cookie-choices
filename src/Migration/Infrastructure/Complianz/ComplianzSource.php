<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\Complianz;

use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Domain\MigrationSource;

/**
 * Imports the settings of Complianz, the free version 7 and the versions before.
 *
 * Complianz is GPL, the import reads its settings in the database, no code of it is used.
 */
final readonly class ComplianzSource implements MigrationSource
{
    public const ID = 'complianz';

    public function __construct(
        private ComplianzStorage $storage,
        private ComplianzBanner $banner,
        private ComplianzServices $services,
    ) {
    }

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return ComplianzBanner::NAME;
    }

    public function isFound(): bool
    {
        return $this->storage->hasSettings();
    }

    public function read(): ImportedSettings
    {
        return new ImportedSettings(
            [...$this->banner->settings(), ...$this->services->settings()],
            $this->services->vendorsInUse(),
            [
                ...$this->banner->notes(),
                ...$this->services->notes(),
                \__(
                    'Translations are not imported: write the texts again in WPML or Polylang.',
                    'italy-cookie-choices'
                ),
                \__(
                    'Deactivate Complianz after the import, or the site shows two banners. Its settings stay.',
                    'italy-cookie-choices'
                ),
            ]
        );
    }
}
