<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\UI;

use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\ConsentLog\Domain\ConsentId;
use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyCookieChoices\ConsentLog\Domain\ConsentRecord;
use ItalyCookieChoices\ConsentLog\UI\ConsentStatisticsEndpoint;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentStatisticsEndpointTest extends WPTestCase
{
    use BuildsContainer;

    private const PATH = '/italy-cookie-choices/v1/statistics';

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1', 'consent_log' => '1']);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['wp_rest_server']);
        parent::tearDown();
    }

    public function testItRegistersOnRestApiInit(): void
    {
        $this->assertSame(
            ['rest_api_init' => 'register'],
            \iterator_to_array($this->service(ConsentStatisticsEndpoint::class)->getSubscribedEvents())
        );
    }

    public function testTheAdministratorsReadTheStatisticsByMonth(): void
    {
        $this->logIn('administrator');
        $log = $this->service(ConsentLog::class);
        $log->add($this->record(['necessary', 'preferences', 'statistics', 'marketing']));
        $log->add($this->record(['necessary']));

        $response = $this->get();

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame(
            [
                ['id' => 'preferences', 'label' => 'Preferences'],
                ['id' => 'statistics', 'label' => 'Statistics'],
                ['id' => 'marketing', 'label' => 'Marketing'],
            ],
            $data['categories'] ?? null
        );
        $this->assertIsArray($data['months'] ?? null);
        $month = $data['months'][0] ?? null;
        $this->assertIsArray($month);
        $thisMonth = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m');
        $this->assertSame($thisMonth, $month['month'] ?? null);
        $this->assertSame(2, $month['choices'] ?? null);
        $this->assertSame(1, $month['acceptAll'] ?? null);
        $this->assertSame(1, $month['rejectAll'] ?? null);
    }

    public function testTheOtherUsersCannotReadThem(): void
    {
        $this->logIn('editor');

        $this->assertSame(403, $this->get()->get_status());
    }

    private function logIn(string $role): void
    {
        $userId = self::factory()->user->create(['role' => $role]);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);
    }

    /**
     * @param list<string> $categories
     */
    private function record(array $categories): ConsentRecord
    {
        return new ConsentRecord(
            new ConsentId('3f2504e0-4f89-41d3-9a0c-0305e82c3301'),
            $categories,
            new PolicyVersion('1'),
            new \DateTimeImmutable()
        );
    }

    private function get(): \WP_REST_Response
    {
        \remove_all_actions('rest_api_init');
        \add_action('rest_api_init', [$this->service(ConsentStatisticsEndpoint::class), 'register']);

        return \rest_do_request(new \WP_REST_Request('GET', self::PATH));
    }
}
