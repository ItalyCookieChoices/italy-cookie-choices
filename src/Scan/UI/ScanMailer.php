<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI;

use ItalyCookieChoices\Blocking\Application\VendorSettings;
use ItalyCookieChoices\Scan\Domain\Report;
use ItalyCookieChoices\Settings\UI\SettingsTab;

/**
 * The email of the news of the scheduled scan, to the admin address of the site.
 */
final readonly class ScanMailer
{
    public function __construct(
        private VendorSettings $vendors,
    ) {
    }

    public function send(Report $report): void
    {
        $site = \wp_specialchars_decode($this->option('blogname'), \ENT_QUOTES);

        \wp_mail(
            $this->option('admin_email'),
            \sprintf(
                /* translators: %s: the name of the site. */
                \__('[%s] The scan of the site found new vendors to check', 'italy-cookie-choices'),
                $site
            ),
            \implode("\n\n", \array_filter([
                \__('The scheduled scan of Cookie Choices found:', 'italy-cookie-choices'),
                $this->section(
                    \__('Vendors not blocked:', 'italy-cookie-choices'),
                    \array_map($this->nameOf(...), $report->newVendors)
                ),
                $this->section(\__('Unknown third parties:', 'italy-cookie-choices'), $report->newHosts),
                \sprintf(
                    /* translators: %s: the address of the settings page. */
                    \__('Check them in the Blocking tab: %s', 'italy-cookie-choices'),
                    SettingsTab::Blocking->url()
                ),
            ]))
        );
    }

    private function option(string $name): string
    {
        $value = \get_option($name);

        return \is_string($value) ? $value : '';
    }

    /**
     * @param list<string> $items
     */
    private function section(string $title, array $items): string
    {
        return $items === [] ? '' : $title . "\n* " . \implode("\n* ", $items);
    }

    private function nameOf(string $id): string
    {
        foreach ($this->vendors->all() as $vendor) {
            if ($vendor['id'] === $id) {
                return $vendor['name'];
            }
        }

        return $id;
    }
}
