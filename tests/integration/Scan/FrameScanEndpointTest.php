<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\Application\ScanTokens;
use ItalyCookieChoices\Scan\UI\FrameScanEndpoint;
use ItalyCookieChoices\Scan\UI\ScanRequest;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class FrameScanEndpointTest extends WPTestCase
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
        parent::tearDown();
    }

    public function testItRegistersOnTheRestApi(): void
    {
        $this->assertSame(
            ['rest_api_init' => 'register'],
            \iterator_to_array($this->service(FrameScanEndpoint::class)->getSubscribedEvents())
        );
    }

    public function testTheAdminGetsThePageInScanModeWithAOneTimeToken(): void
    {
        $this->logInAs('administrator');
        $page = \home_url('/sample-page/?lang=it');

        $response = $this->request(['url' => $page]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $url = $data['url'] ?? null;
        $this->assertIsString($url);
        $this->assertTrue(\str_starts_with($url, \home_url('/sample-page/?lang=it&' . ScanRequest::QUERY_ARG . '=')));
        \parse_str((string) \wp_parse_url($url, \PHP_URL_QUERY), $query);
        $token = $query[ScanRequest::QUERY_ARG] ?? null;
        $this->assertIsString($token);
        $this->assertTrue($this->service(ScanTokens::class)->consume($token));
    }

    public function testThePagesOfOtherSitesAreNotScanned(): void
    {
        $this->logInAs('administrator');

        $this->assertSame(400, $this->request(['url' => 'https://example.org/'])->get_status());
        $this->assertSame(400, $this->request(['url' => 'not a url'])->get_status());
    }

    public function testOnlyTheAdministratorsScan(): void
    {
        $this->logInAs('editor');

        $this->assertSame(403, $this->request(['url' => \home_url('/')])->get_status());
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(array $body): \WP_REST_Response
    {
        $request = new \WP_REST_Request('POST', '/italy-cookie-choices/v1/scan/frame');
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
