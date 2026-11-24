<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\UI\ScanEndpoint;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScanEndpointTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        global $wp_rest_server;
        $wp_rest_server = new \WP_REST_Server();
        \do_action('rest_api_init', $wp_rest_server);
        // The page of the site, read without a real loopback request.
        \add_filter('pre_http_request', static fn(): array => [
            'response' => ['code' => 200, 'message' => 'OK'],
            'body' => '<iframe src="https://www.youtube.com/embed/abc"></iframe>',
            'headers' => [],
            'cookies' => [],
        ]);
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
            \iterator_to_array($this->service(ScanEndpoint::class)->getSubscribedEvents())
        );
    }

    public function testTheAdminFindsThePagesOfASelection(): void
    {
        $this->logInAs('administrator');
        $page = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $this->assertIsInt($page);

        $response = $this->request('/targets', ['home' => true, 'posts' => [$page]]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame([0, $page], \array_column(\is_array($data['targets'] ?? null) ? $data['targets'] : [], 'id'));
        $this->assertFalse($data['truncated'] ?? null);
    }

    public function testTheAdminScansABatchOfPages(): void
    {
        $this->logInAs('administrator');

        $response = $this->request('/pages', ['ids' => [0]]);

        $this->assertSame(200, $response->get_status());
        $this->assertSame(
            ['pages' => [[
                'id' => 0,
                'url' => \home_url('/'),
                'vendors' => ['youtube'],
                'hosts' => [],
                'googleFonts' => false,
                'error' => '',
            ]]],
            $response->get_data()
        );
    }

    public function testABatchHasTenPagesAtMost(): void
    {
        $this->logInAs('administrator');

        $this->assertSame(400, $this->request('/pages', ['ids' => \range(0, 10)])->get_status());
    }

    public function testOnlyTheAdministratorsScan(): void
    {
        $this->logInAs('editor');

        $this->assertSame(403, $this->request('/targets', ['home' => true])->get_status());
        $this->assertSame(403, $this->request('/pages', ['ids' => [0]])->get_status());
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(string $route, array $body): \WP_REST_Response
    {
        $request = new \WP_REST_Request('POST', '/italy-cookie-choices/v1/scan' . $route);
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
