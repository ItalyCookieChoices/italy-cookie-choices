<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Dashboard\UI;

use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\ConsentLog\Domain\ConsentId;
use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyCookieChoices\ConsentLog\Domain\ConsentRecord;
use ItalyCookieChoices\Dashboard\UI\DashboardWidget;
use ItalyCookieChoices\Scan\Application\ScanReports;
use ItalyCookieChoices\Scan\Domain\Report;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyStrap\Config\Config;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class DashboardWidgetTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;

    public function setUp(): void
    {
        parent::setUp();
        require_once ABSPATH . 'wp-admin/includes/dashboard.php';
        \set_current_screen('dashboard');
        \update_option(Schema::OPTION, ['active' => '1', 'consent_log' => '1']);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['wp_meta_boxes']);
        parent::tearDown();
    }

    public function testItIsAddedToTheDashboardAndLoadsItsStyle(): void
    {
        $this->assertSame(
            ['wp_dashboard_setup' => 'register', 'admin_enqueue_scripts' => 'enqueue'],
            \iterator_to_array($this->sut()->getSubscribedEvents())
        );
    }

    public function testTheAdministratorsSeeTheWidget(): void
    {
        $this->logInAs('administrator');

        $this->sut()->register();

        $this->assertArrayHasKey(DashboardWidget::ID, $this->widgets());
    }

    public function testTheOtherUsersDoNotSeeIt(): void
    {
        $this->logInAs('editor');

        $this->sut()->register();

        $this->assertArrayNotHasKey(DashboardWidget::ID, $this->widgets());
    }

    public function testTheStatusWarnsAboutWhatIsMissing(): void
    {
        $status = $this->siteStatus();

        $this->assertSame('ok', $status['banner']['state'] ?? null);
        $this->assertSame('warning', $status['policy']['state'] ?? null);
        $this->assertSame('ok', $status['consent-log']['state'] ?? null);
        $this->assertSame('warning', $status['scan']['state'] ?? null);
        $this->assertArrayNotHasKey('consent-mode', $status);
        $this->assertStringContainsString('page=italy-cookie-choices&tab=texts', $status['policy']['url'] ?? '');
    }

    public function testTheStatusOfAConfiguredSite(): void
    {
        \update_option(Schema::OPTION, [
            'active' => '1',
            'consent_log' => '1',
            'url' => 'https://example.com/privacy/',
            'ga_measurement_id' => 'G-ICC123',
            'consent_mode' => '1',
            'global_privacy_control' => '1',
        ]);
        $this->service(ScanReports::class)->save(
            new Report(new \DateTimeImmutable('2026-11-01 10:00:00'), 3, 0, [], ['cdn.example.org'], [], [])
        );

        $status = $this->siteStatus();

        $this->assertSame('ok', $status['policy']['state'] ?? null);
        $this->assertSame('ok', $status['consent-mode']['state'] ?? null);
        $this->assertSame('ok', $status['gpc']['state'] ?? null);
        $this->assertSame('warning', $status['scan']['state'] ?? null);
        $this->assertStringContainsString('1 unknown third party', $status['scan']['value'] ?? '');
    }

    public function testTheConsentsOfThisMonth(): void
    {
        $log = $this->service(ConsentLog::class);
        $log->add($this->record(['necessary', 'preferences', 'statistics', 'marketing']));
        $log->add($this->record(['necessary', 'statistics']));
        $log->add($this->record(['necessary']));

        $consents = $this->sut()->data()['consents'];

        $this->assertSame(['choices' => 3, 'acceptAll' => 1, 'rejectAll' => 1, 'partial' => 1], $consents);
    }

    public function testWithoutTheConsentLogThereAreNoConsents(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $data = $this->sut()->data();

        $this->assertNull($data['consents']);
        $this->assertSame('warning', $this->siteStatus()['consent-log']['state'] ?? null);
    }

    public function testItPrintsTheStatusAndTheConsents(): void
    {
        $this->service(ConsentLog::class)->add($this->record(['necessary']));

        \ob_start();
        $this->sut()->render();
        $html = (string) \ob_get_clean();

        $this->assertStringContainsString('Banner', $html);
        $this->assertStringContainsString('Policy link', $html);
        $this->assertStringContainsString('Consents this month', $html);
        $this->assertStringContainsString('Refused', $html);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function siteStatus(): array
    {
        return \array_column($this->sut()->data()['items'], null, 'id');
    }

    /**
     * @return array<array-key, mixed>
     */
    private function widgets(): array
    {
        $metaBoxes = $GLOBALS['wp_meta_boxes'] ?? [];
        $boxes = (new Config(\is_array($metaBoxes) ? $metaBoxes : []))->get(['dashboard', 'normal', 'core']);

        return \is_array($boxes) ? $boxes : [];
    }

    /**
     * @param list<string> $categories
     */
    private function record(array $categories): ConsentRecord
    {
        return new ConsentRecord(
            new ConsentId(\wp_generate_uuid4()),
            $categories,
            new PolicyVersion('1'),
            new \DateTimeImmutable()
        );
    }

    private function sut(): DashboardWidget
    {
        return $this->service(DashboardWidget::class);
    }
}
