<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\CookieNotice;

/**
 * Reads the settings Cookie Notice & Compliance keeps in the database, also when it is deactivated.
 *
 * On a network where it is active for every site with the global settings, they are network options.
 * The Compliance app keeps on the site only the design and the blocking catalogue, some entries as objects.
 */
final readonly class CookieNoticeStorage
{
    private const OPTIONS = 'cookie_notice_options';
    private const STATUS = 'cookie_notice_status';
    private const APP_DESIGN = 'cookie_notice_app_design';
    private const APP_BLOCKING = 'cookie_notice_app_blocking';
    private const ACTIVE_APP = 'active';

    public function hasSettings(): bool
    {
        return $this->options() !== [];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function options(): array
    {
        return $this->option(self::OPTIONS);
    }

    /**
     * Whether the site uses the Compliance app: the banner and its texts come from the app.
     */
    public function usesTheApp(): bool
    {
        $status = $this->option(self::STATUS);
        $options = $this->options();

        return ($status['status'] ?? '') === self::ACTIVE_APP
            && \is_string($options['app_id'] ?? null)
            && $options['app_id'] !== '';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function appDesign(): array
    {
        return $this->option(self::APP_DESIGN);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function appBlocking(): array
    {
        return $this->option(self::APP_BLOCKING);
    }

    /**
     * The option of the site, or of the network when the global settings of Cookie Notice override it.
     * Its objects become arrays.
     *
     * @return array<array-key, mixed>
     */
    private function option(string $name): array
    {
        $network = \is_multisite() ? \get_site_option(self::OPTIONS) : null;
        $global = \is_array($network) && ($network['global_override'] ?? false) === true;
        $value = $global ? \get_site_option($name) : \get_option($name);
        $decoded = \json_decode((string) \wp_json_encode($value), true);

        return \is_array($decoded) ? $decoded : [];
    }
}
