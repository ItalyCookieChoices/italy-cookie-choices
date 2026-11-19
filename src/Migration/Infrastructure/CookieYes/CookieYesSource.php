<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\CookieYes;

use ItalyCookieChoices\Migration\Domain\ImportedSettings;
use ItalyCookieChoices\Migration\Domain\MigrationSource;

/**
 * Imports the settings of CookieYes, version 3 and the versions 2 not upgraded.
 *
 * CookieYes is GPL, the import reads its settings in the database, no code of it is used.
 */
final readonly class CookieYesSource implements MigrationSource
{
    public const ID = 'cookieyes';

    public function __construct(
        private CookieYesStorage $storage,
        private CookieYesBanner $banner,
        private CookieYesCookies $cookies,
        private CookieYesLegacy $legacy,
    ) {
    }

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return CookieYesBanner::NAME;
    }

    public function isFound(): bool
    {
        return $this->storage->hasSettings();
    }

    public function read(): ImportedSettings
    {
        $common = [
            \__(
                'Translations are not imported: write the texts again in WPML or Polylang.',
                'italy-cookie-choices'
            ),
            \__(
                'Deactivate CookieYes after the import, or the site shows two banners. Its settings stay.',
                'italy-cookie-choices'
            ),
        ];

        if ($this->storage->isLegacy()) {
            return new ImportedSettings($this->legacy->settings(), [], [...$this->legacy->notes(), ...$common]);
        }

        return new ImportedSettings(
            [...$this->banner->settings(), ...$this->cookies->settings()],
            $this->cookies->vendorsInUse(),
            [...$this->banner->notes(), ...$common]
        );
    }
}
