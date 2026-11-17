<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\Application\ScanReports;
use ItalyCookieChoices\Scan\Application\ScanSchedule;
use ItalyCookieChoices\Scan\Domain\Frequency;
use ItalyCookieChoices\Scan\Domain\Report;
use ItalyCookieChoices\Scan\Domain\Schedule;
use ItalyCookieChoices\Scan\Domain\Selection;
use ItalyCookieChoices\Scan\UI\ScanCron;
use ItalyCookieChoices\Scan\UI\ScanNotice;
use ItalyCookieChoices\Scan\UI\ScheduleData;
use ItalyCookieChoices\Scan\UI\ScheduleEndpoint;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScheduleUiTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        global $wp_rest_server;
        $wp_rest_server = new \WP_REST_Server();
        \do_action('rest_api_init', $wp_rest_server);
    }

    public function tearDown(): void
    {
        global $wp_rest_server;
        $wp_rest_server = null;
        unset($_REQUEST['_wpnonce']);
        \wp_clear_scheduled_hook(ScanCron::RUN);
        parent::tearDown();
    }

    public function testThePageGetsTheScheduleWithTheNamesAndTheLastReport(): void
    {
        $category = self::factory()->category->create(['name' => 'News']);
        $post = self::factory()->post->create(['post_title' => 'About', 'post_status' => 'publish']);
        $this->assertIsInt($category);
        $this->assertIsInt($post);
        \update_option('admin_email', 'owner@example.com');
        (new ScanSchedule())->save(
            new Schedule(Frequency::Weekly, new Selection(true, ['page'], [$category], [$post]), true)
        );
        (new ScanReports())->save($this->report(['youtube'], ['cdn.example.org']));

        $data = $this->service(ScheduleData::class)->data();

        $this->assertSame('weekly', $data['frequency']);
        $this->assertTrue($data['home']);
        $this->assertSame(['page'], $data['postTypes']);
        $this->assertSame([['id' => $category, 'name' => 'News']], $data['categories']);
        $this->assertSame([['id' => $post, 'name' => 'About']], $data['posts']);
        $this->assertTrue($data['email']);
        $this->assertSame('owner@example.com', $data['adminEmail']);
        $this->assertNotNull($data['report']);
        $this->assertSame(3, $data['report']['pages']);
        $this->assertSame(['YouTube'], $data['report']['vendors']);
        $this->assertSame(['cdn.example.org'], $data['report']['hosts']);
    }

    public function testTheAdminSavesTheScheduleAndItsEvent(): void
    {
        $this->logInAs('administrator');

        $response = $this->request([
            'frequency' => 'monthly',
            'home' => true,
            'postTypes' => ['post'],
            'categories' => [],
            'posts' => [],
            'email' => false,
        ]);

        $this->assertSame(200, $response->get_status());
        $this->assertEquals(
            new Schedule(Frequency::Monthly, new Selection(true, ['post'])),
            (new ScanSchedule())->get()
        );
        $this->assertSame(Frequency::MONTHLY_RECURRENCE, \wp_get_schedule(ScanCron::RUN));
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame('monthly', $data['frequency'] ?? null);
    }

    public function testOnlyTheAdministratorsSaveTheSchedule(): void
    {
        $this->logInAs('editor');

        $this->assertSame(403, $this->request(['frequency' => 'daily'])->get_status());
    }

    public function testTheNoticeShowsTheNewsToTheAdministrators(): void
    {
        (new ScanReports())->save($this->report(['youtube'], ['cdn.example.org']));
        $this->logInAs('administrator');

        $html = $this->notice();

        $this->assertStringContainsString('YouTube', $html);
        $this->assertStringContainsString('cdn.example.org', $html);
        $this->assertStringContainsString(
            \esc_url(\admin_url('admin.php?page=italy-cookie-choices&tab=blocking')),
            $html
        );
        $this->assertStringContainsString('action=' . ScanNotice::ACTION, $html);

        $this->logInAs('editor');
        $this->assertSame('', $this->notice());
    }

    public function testThereIsNoNoticeWithoutNews(): void
    {
        (new ScanReports())->save($this->report([], []));
        $this->logInAs('administrator');

        $this->assertSame('', $this->notice());
    }

    public function testTheAdminIgnoresTheNews(): void
    {
        (new ScanReports())->save($this->report(['youtube'], []));
        $this->logInAs('administrator');
        $_REQUEST['_wpnonce'] = \wp_create_nonce(ScanNotice::ACTION);
        // Stops the ignore before its exit.
        $filter = static function (string $location): string {
            if ($location !== '') {
                throw new \RuntimeException($location);
            }

            return $location;
        };
        \add_filter('wp_redirect', $filter);

        try {
            $this->service(ScanNotice::class)->ignore();
            $this->fail('The ignore did not redirect.');
        } catch (\RuntimeException) {
            $report = (new ScanReports())->last();
            $this->assertNotNull($report);
            $this->assertFalse($report->hasNews());
            $this->assertSame(['youtube'], $report->vendors);
        } finally {
            \remove_filter('wp_redirect', $filter);
        }
    }

    /**
     * @param list<string> $vendors
     * @param list<string> $hosts
     */
    private function report(array $vendors, array $hosts): Report
    {
        $date = new \DateTimeImmutable('2026-11-01T10:00:00+00:00');

        return new Report($date, 3, 0, $vendors, $hosts, $vendors, $hosts);
    }

    private function notice(): string
    {
        \ob_start();
        $this->service(ScanNotice::class)->show();

        return (string) \ob_get_clean();
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(array $body): \WP_REST_Response
    {
        $request = new \WP_REST_Request('POST', '/italy-cookie-choices/v1' . ScheduleEndpoint::ROUTE);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) \wp_json_encode($body));

        return \rest_do_request($request);
    }

    private function logInAs(string $role): void
    {
        $userId = self::factory()->user->create(['role' => $role]);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);
    }
}
