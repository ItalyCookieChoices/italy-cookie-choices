<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\CookieAdmin;

/**
 * Reads the settings CookieAdmin keeps in the database, also when it is deactivated: the banner of the law
 * in use, GDPR by default, its general settings, and the cookies of its scans in its own table.
 *
 * @phpstan-type ScannedCookie array{name: string, category: string, description: string, seconds: int|null}
 */
final readonly class CookieAdminStorage
{
    private const VERSION = 'cookieadmin_version';
    private const LAW = 'cookieadmin_law';
    private const GDPR = 'cookieadmin_gdpr';
    private const BANNERS = 'cookieadmin_consent_settings';
    private const SETTINGS = 'cookieadmin_settings';
    private const COOKIES = 'cookieadmin_cookies';

    public function __construct(
        private \wpdb $database,
    ) {
    }

    public function hasSettings(): bool
    {
        return \get_option(self::VERSION) !== false;
    }

    /**
     * The settings of the banner of the law in use, only the ones changed from the defaults of CookieAdmin.
     *
     * @return array<array-key, mixed>
     */
    public function banner(): array
    {
        $law = \get_option(self::LAW, self::GDPR);
        $banners = \get_option(self::BANNERS);
        $banner = \is_array($banners) ? $banners[\is_string($law) ? $law : self::GDPR] ?? null : null;

        return \is_array($banner) ? $banner : [];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function settings(): array
    {
        $settings = \get_option(self::SETTINGS);

        return \is_array($settings) ? $settings : [];
    }

    /**
     * The cookies found by the scans of CookieAdmin, with their lifetime: null for a session cookie.
     *
     * @return list<ScannedCookie>
     */
    public function cookies(): array
    {
        if (!$this->hasSettings()) {
            return [];
        }

        // The table is missing before the first scan: no rows, without an error.
        $suppressed = $this->database->suppress_errors();
        $rows = $this->database->get_results($this->database->prepare(
            'SELECT cookie_name, category, description, expires, max_age, scan_timestamp FROM %i ORDER BY id ASC',
            $this->database->prefix . self::COOKIES
        ), \ARRAY_A);
        $this->database->suppress_errors($suppressed);

        return \array_map($this->cookie(...), $rows ?? []);
    }

    /**
     * @param array<array-key, mixed> $row
     * @return ScannedCookie
     */
    private function cookie(array $row): array
    {
        return [
            'name' => $this->string($row['cookie_name'] ?? ''),
            'category' => $this->string($row['category'] ?? ''),
            'description' => $this->string($row['description'] ?? ''),
            'seconds' => $this->seconds($row),
        ];
    }

    /**
     * The max-age, or the time from the scan to the expiry, null for a session cookie.
     *
     * @param array<array-key, mixed> $row
     */
    private function seconds(array $row): ?int
    {
        $maxAge = $row['max_age'] ?? null;
        if (\is_numeric($maxAge) && (int) $maxAge > 0) {
            return (int) $maxAge;
        }

        $expires = \strtotime($this->string($row['expires'] ?? '') . ' UTC');
        $scanned = $row['scan_timestamp'] ?? null;
        if ($expires === false || !\is_numeric($scanned) || (int) $scanned <= 0) {
            return null;
        }

        return \max(0, $expires - (int) $scanned);
    }

    private function string(mixed $value): string
    {
        return \is_scalar($value) ? \trim((string) $value) : '';
    }
}
