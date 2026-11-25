<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\SureCookie;

/**
 * Reads the settings SureCookie keeps in the database, also when it is deactivated: its settings option,
 * the services the site owner added from its catalog and the cookies its scans found, by category.
 *
 * @phpstan-type Service array{slug: string, label: string, cookies: list<string>}
 */
final readonly class SureCookieStorage
{
    private const SETTINGS = 'surecookie_settings';
    private const SERVICES = 'surecookie_installed_services';
    private const SCANNED_COOKIES = 'surecookie_scanned_cookies';

    public function hasSettings(): bool
    {
        return \is_array(\get_option(self::SETTINGS));
    }

    /**
     * @return array<array-key, mixed>
     */
    public function settings(): array
    {
        return $this->option(self::SETTINGS);
    }

    /**
     * The services added from the catalog, with the ids of their custom cookies.
     *
     * @return list<Service>
     */
    public function services(): array
    {
        $installed = $this->option(self::SERVICES)['installed'] ?? [];
        $services = [];
        foreach (\is_array($installed) ? $installed : [] as $slug => $service) {
            if (!\is_array($service)) {
                continue;
            }

            $cookies = \is_array($service['cookie_ids'] ?? null) ? $service['cookie_ids'] : [];
            $services[] = [
                'slug' => (string) $slug,
                'label' => \is_string($service['label'] ?? null) ? $service['label'] : (string) $slug,
                'cookies' => \array_values(\array_filter($cookies, \is_string(...))),
            ];
        }

        return $services;
    }

    /**
     * The rows of the scans, by the id of their category.
     *
     * @return array<array-key, mixed>
     */
    public function scannedCookies(): array
    {
        return $this->option(self::SCANNED_COOKIES);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function option(string $name): array
    {
        if (!$this->hasSettings()) {
            return [];
        }

        $value = \get_option($name);

        return \is_array($value) ? $value : [];
    }
}
