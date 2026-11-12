<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Settings\UI;

use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Settings\UI\CopySettingsEndpoint;
use ItalyCookieChoices\Settings\UI\NetworkSettingsEndpoint;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class NetworkEndpointsTest extends WPTestCase
{
    use BuildsContainer;

    private const NETWORK = '/italy-cookie-choices/v1/network';
    private const COPY = '/italy-cookie-choices/v1/network/copy';

    public function setUp(): void
    {
        parent::setUp();
        global $wp_rest_server;
        $wp_rest_server = new \WP_REST_Server();
        \do_action('rest_api_init', $wp_rest_server);
        $this->service(NetworkSettingsEndpoint::class)->register();
        $this->service(CopySettingsEndpoint::class)->register();
    }

    public function tearDown(): void
    {
        global $wp_rest_server;
        $wp_rest_server = null;
        \delete_site_option(NetworkSettings::OPTION);
        parent::tearDown();
    }

    public function testTheyRegisterOnTheRestApi(): void
    {
        $this->assertSame(
            ['rest_api_init' => 'register'],
            \iterator_to_array($this->service(NetworkSettingsEndpoint::class)->getSubscribedEvents())
        );
        $this->assertSame(
            ['rest_api_init' => 'register'],
            \iterator_to_array($this->service(CopySettingsEndpoint::class)->getSubscribedEvents())
        );
    }

    public function testTheSuperAdminReadsAndSavesTheNetworkSettings(): void
    {
        $this->logInAsSuperAdmin();

        $this->assertSame(['shared' => false], $this->request('GET', self::NETWORK)->get_data());

        $response = $this->request('POST', self::NETWORK, ['shared' => true]);

        $this->assertSame(200, $response->get_status());
        $this->assertSame(['shared' => true], $response->get_data());
        $this->assertTrue((new NetworkSettings())->sharesTheMainSite());

        $this->request('POST', self::NETWORK, ['shared' => false]);
        $this->assertFalse((new NetworkSettings())->sharesTheMainSite());
    }

    public function testTheSuperAdminCopiesTheSettings(): void
    {
        $this->logInAsSuperAdmin();
        $source = $this->site(['banner' => '2', 'text' => 'Source']);
        $target = $this->site(['banner' => '3', 'text' => 'Target']);

        $response = $this->request('POST', self::COPY, [
            'source' => $source,
            'targets' => [$target],
            'texts' => false,
        ]);

        $this->assertSame(200, $response->get_status());
        $this->assertSame(['copied' => [$target]], $response->get_data());
        $option = \get_blog_option($target, Schema::OPTION);
        $this->assertIsArray($option);
        $this->assertSame('2', $option['banner'] ?? null);
        $this->assertSame('Target', $option['text'] ?? null);
    }

    public function testTheCopyExplainsWhyItFails(): void
    {
        $this->logInAsSuperAdmin();
        $source = $this->site(['banner' => '2']);

        $response = $this->request('POST', self::COPY, ['source' => $source, 'targets' => [$source]]);

        $this->assertSame(400, $response->get_status());
        $error = $response->as_error();
        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertSame('italy_cookie_choices_invalid_copy', $error->get_error_code());
    }

    public function testOnlyTheSuperAdminsUseThem(): void
    {
        $userId = self::factory()->user->create(['role' => 'administrator']);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);
        $source = $this->site(['banner' => '2']);
        $target = $this->site(['banner' => '3']);

        $this->assertSame(403, $this->request('GET', self::NETWORK)->get_status());
        $this->assertSame(403, $this->request('POST', self::NETWORK, ['shared' => true])->get_status());
        $this->assertSame(
            403,
            $this->request('POST', self::COPY, ['source' => $source, 'targets' => [$target]])->get_status()
        );
        $this->assertFalse((new NetworkSettings())->sharesTheMainSite());
        $option = \get_blog_option($target, Schema::OPTION);
        $this->assertIsArray($option);
        $this->assertSame('3', $option['banner'] ?? null);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(string $method, string $route, array $body = []): \WP_REST_Response
    {
        $request = new \WP_REST_Request($method, $route);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) \wp_json_encode($body));

        return \rest_do_request($request);
    }

    /**
     * @param array<string, mixed> $option
     */
    private function site(array $option): int
    {
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);
        \switch_to_blog($siteId);
        \update_option(Schema::OPTION, $option);
        \restore_current_blog();

        return $siteId;
    }

    private function logInAsSuperAdmin(): void
    {
        $userId = self::factory()->user->create(['role' => 'administrator']);
        $this->assertIsInt($userId);
        \grant_super_admin($userId);
        \wp_set_current_user($userId);
    }
}
