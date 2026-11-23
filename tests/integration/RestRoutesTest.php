<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration;

use ItalyCookieChoices\RestHandler;
use ItalyCookieChoices\RestRoutes;
use ItalyCookieChoices\Tests\Support\LogsIn;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class RestRoutesTest extends WPTestCase
{
    use LogsIn;

    private const ROUTE = '/italy-cookie-choices/v1/test';

    public function setUp(): void
    {
        parent::setUp();
        global $wp_rest_server;
        $wp_rest_server = new \WP_REST_Server();
        \remove_all_actions('rest_api_init');
    }

    public function tearDown(): void
    {
        global $wp_rest_server;
        $wp_rest_server = null;
        parent::tearDown();
    }

    public function testARouteForTheAdministratorsIsInTheNamespaceOfThePlugin(): void
    {
        $this->register(static function (RestRoutes $routes): void {
            $routes->forAdministrators('/test', new RestHandler(
                \WP_REST_Server::READABLE,
                static fn(): \WP_REST_Response => new \WP_REST_Response('read')
            ));
        });

        $this->logInAs('editor');
        $this->assertSame(403, $this->request('GET')->get_status());
        $this->logInAs('administrator');
        $response = $this->request('GET');
        $this->assertSame(200, $response->get_status());
        $this->assertSame('read', $response->get_data());
    }

    public function testARouteForEveryoneNeedsNoLogin(): void
    {
        $this->register(static function (RestRoutes $routes): void {
            $routes->forEveryone('/test', new RestHandler(
                \WP_REST_Server::CREATABLE,
                static fn(\WP_REST_Request $request): \WP_REST_Response => new \WP_REST_Response(
                    $request->get_param('name')
                ),
                ['name' => ['type' => 'string', 'required' => true]]
            ));
        });

        $this->assertSame(400, $this->request('POST')->get_status());
        $this->assertSame('icc', $this->request('POST', ['name' => 'icc'])->get_data());
    }

    public function testARouteHasAHandlerForEachMethodWithItsPermission(): void
    {
        $this->register(static function (RestRoutes $routes): void {
            $routes->forUsersWho(
                static fn(): bool => \current_user_can('edit_posts'),
                '/test',
                new RestHandler(\WP_REST_Server::READABLE, static fn() => new \WP_REST_Response('read')),
                new RestHandler(\WP_REST_Server::EDITABLE, static fn() => new \WP_REST_Response('save'))
            );
        });

        $this->assertSame(401, $this->request('GET')->get_status());
        $this->logInAs('editor');
        $this->assertSame('read', $this->request('GET')->get_data());
        $this->assertSame('save', $this->request('PUT')->get_data());
    }

    /**
     * @param \Closure(RestRoutes): void $registration
     */
    private function register(\Closure $registration): void
    {
        \add_action('rest_api_init', static fn() => $registration(new RestRoutes()));
        global $wp_rest_server;
        \do_action('rest_api_init', $wp_rest_server);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(string $method, array $body = []): \WP_REST_Response
    {
        $request = new \WP_REST_Request($method, self::ROUTE);
        $request->set_body_params($body);

        return \rest_do_request($request);
    }
}
