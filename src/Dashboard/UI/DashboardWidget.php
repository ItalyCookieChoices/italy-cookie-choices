<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Dashboard\UI;

use ItalyCookieChoices\ConsentLog\Application\LogSettings;
use ItalyCookieChoices\ConsentLog\UI\StatisticsReport;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyCookieChoices\Settings\UI\SettingsTab;
use ItalyStrap\Asset\AssetLoader;
use ItalyStrap\Asset\BuildMetadataFile;
use ItalyStrap\Asset\Handle;
use ItalyStrap\Asset\Style;
use ItalyStrap\Config\Config;
use ItalyStrap\Event\SubscriberInterface;
use ItalyStrap\View\ViewInterface;
use SplFileInfo;

/**
 * The widget of the dashboard: the status of the settings and the consents of the month, computed on the site.
 *
 * @phpstan-import-type Item from SiteStatus
 * @phpstan-type Consents array{choices: int, acceptAll: int, rejectAll: int, partial: int}
 */
final readonly class DashboardWidget implements SubscriberInterface
{
    public const ID = 'italy_cookie_choices_status';

    private const HANDLE = 'italy-cookie-choices-dashboard';
    private const SCRIPT = 'dashboard.js';
    private const STYLE = 'dashboard.css';
    private const TEMPLATE = 'dashboard-widget';
    private const DASHBOARD = 'index.php';
    private const MONTH = 'Y-m';

    /**
     * @param string $buildPath The assets/build folder, with the trailing slash.
     * @param string $buildUrl The assets/build URL, with the trailing slash.
     */
    public function __construct(
        private SiteStatus $status,
        private StatisticsReport $statistics,
        private LogSettings $log,
        private ViewInterface $view,
        private AssetLoader $assets,
        private string $buildPath,
        private string $buildUrl,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_dashboard_setup' => 'register';
        yield 'admin_enqueue_scripts' => 'enqueue';
    }

    public function register(): void
    {
        if (!\current_user_can(SettingsPage::CAPABILITY)) {
            return;
        }

        \wp_add_dashboard_widget(self::ID, \__('Cookie Choices', 'italy-cookie-choices'), $this->render(...));
    }

    public function enqueue(string $hook): void
    {
        $file = new BuildMetadataFile(new SplFileInfo($this->buildPath . self::SCRIPT));
        if ($hook !== self::DASHBOARD || !$file->exists() || !\current_user_can(SettingsPage::CAPABILITY)) {
            return;
        }

        $this->assets->load(new Style(
            handle: new Handle(self::HANDLE),
            source: $this->buildUrl . self::STYLE,
            version: $file->read()->version(),
        ));
    }

    public function render(): void
    {
        echo $this->view->render(self::TEMPLATE, new Config($this->data())); // phpcs:ignore -- Escaped in the template.
    }

    /**
     * The data the template prints, not escaped yet.
     *
     * @return array{items: list<Item>, consents: Consents|null, logUrl: string}
     */
    public function data(): array
    {
        return [
            'items' => $this->status->items(),
            'consents' => $this->log->isEnabled() ? $this->consentsOfThisMonth() : null,
            'logUrl' => SettingsTab::ConsentLog->url(),
        ];
    }

    /**
     * @return Consents
     */
    private function consentsOfThisMonth(): array
    {
        $consents = ['choices' => 0, 'acceptAll' => 0, 'rejectAll' => 0, 'partial' => 0];
        foreach ($this->statistics->toArray()['months'] as $month) {
            if (($month['month'] ?? '') !== \gmdate(self::MONTH)) {
                continue;
            }

            foreach (\array_keys($consents) as $key) {
                $consents[$key] = \is_int($month[$key] ?? null) ? $month[$key] : 0;
            }
        }

        return $consents;
    }
}
