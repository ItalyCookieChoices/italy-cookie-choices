<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI;

use ItalyCookieChoices\Blocking\Application\VendorSettings;
use ItalyCookieChoices\Scan\Application\ScanReports;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyStrap\Config\Config;
use ItalyStrap\Event\SubscriberInterface;
use ItalyStrap\View\ViewInterface;

/**
 * The news of the scheduled scan for the administrators: the vendors not blocked and the unknown third parties
 * the scan before did not report, until the admin checks or ignores them.
 */
final readonly class ScanNotice implements SubscriberInterface
{
    public const ACTION = 'italy_cookie_choices_ignore_scan';

    private const TEMPLATE = 'scan-notice';
    private const SETTINGS_TAB = 'blocking';

    public function __construct(
        private ScanReports $reports,
        private VendorSettings $vendors,
        private ViewInterface $view,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'admin_notices' => 'show';
        yield 'admin_post_' . self::ACTION => 'ignore';
    }

    public function show(): void
    {
        $report = $this->reports->last();
        if ($report === null || !$report->hasNews() || !\current_user_can(SettingsPage::CAPABILITY)) {
            return;
        }

        $names = \array_column($this->vendors->all(), 'name', 'id');
        $settingsPage = \admin_url('admin.php?page=' . SettingsPage::SLUG);

        echo $this->view->render( // phpcs:ignore -- Escaped in the template.
            self::TEMPLATE,
            new Config([
                'vendors' => \array_map(static fn(string $id): string => $names[$id] ?? $id, $report->newVendors),
                'hosts' => $report->newHosts,
                'checkUrl' => \add_query_arg('tab', self::SETTINGS_TAB, $settingsPage),
                'ignoreUrl' => \wp_nonce_url(\admin_url('admin-post.php?action=' . self::ACTION), self::ACTION),
            ])
        );
    }

    public function ignore(): void
    {
        if (!\current_user_can(SettingsPage::CAPABILITY)) {
            \wp_die(\esc_html__('You cannot change the settings.', 'italy-cookie-choices'), '', ['response' => 403]);
        }

        \check_admin_referer(self::ACTION);
        $report = $this->reports->last();
        if ($report !== null) {
            $this->reports->save($report->withoutNews());
        }

        $referer = \wp_get_referer();
        \wp_safe_redirect(\is_string($referer) ? $referer : \admin_url());
        exit;
    }
}
