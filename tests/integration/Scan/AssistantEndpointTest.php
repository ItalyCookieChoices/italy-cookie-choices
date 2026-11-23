<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Scan\Application\AssistantUsage;
use ItalyCookieChoices\Scan\Application\ScanAssistant;
use ItalyCookieChoices\Scan\UI\AssistantEndpoint;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyCookieChoices\Tests\Support\Scan\FakeTextGenerator;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class AssistantEndpointTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;

    private const REPLY = [
        'hosts' => [
            ['host' => 'cdn.example', 'name' => 'CDN', 'category' => 'marketing', 'purpose' => 'Ads.', 'vendor' => ''],
        ],
        'cookies' => [],
    ];

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
            \iterator_to_array($this->service(AssistantEndpoint::class)->getSubscribedEvents())
        );
    }

    public function testTheAdminGetsTheSuggestions(): void
    {
        $this->registerWith(new FakeTextGenerator(self::REPLY));
        $this->logInAs('administrator');

        $response = $this->request(['hosts' => ['cdn.example'], 'cookies' => []]);

        $this->assertSame(200, $response->get_status());
        $this->assertSame(self::REPLY, $response->get_data());
    }

    public function testAFailureOfTheProviderIsReported(): void
    {
        $this->registerWith(new FakeTextGenerator(null));
        $this->logInAs('administrator');

        $response = $this->request(['hosts' => ['cdn.example']]);

        $this->assertSame(502, $response->get_status());
        $error = $response->as_error();
        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertSame('The provider is down.', $error->get_error_message());
    }

    public function testARequestHasFiftyNamesAtMost(): void
    {
        $this->registerWith(new FakeTextGenerator());
        $this->logInAs('administrator');
        $hosts = \array_map(static fn(int $index): string => "host{$index}.example", \range(1, 51));

        $this->assertSame(400, $this->request(['hosts' => $hosts])->get_status());
    }

    public function testOnlyTheAdministratorsAsk(): void
    {
        $this->registerWith(new FakeTextGenerator());
        $this->logInAs('editor');

        $this->assertSame(403, $this->request(['hosts' => ['cdn.example']])->get_status());
    }

    private function registerWith(FakeTextGenerator $generator): void
    {
        $endpoint = new AssistantEndpoint(new ScanAssistant(
            $generator,
            $this->service(VendorPresets::class),
            $this->service(ConsentPolicyFactory::class),
            $this->service(AssistantUsage::class)
        ));
        global $wp_rest_server;
        $wp_rest_server = new \WP_REST_Server();
        \remove_all_actions('rest_api_init');
        \add_action('rest_api_init', [$endpoint, 'register']);
        \do_action('rest_api_init', $wp_rest_server);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(array $body): \WP_REST_Response
    {
        $request = new \WP_REST_Request('POST', '/italy-cookie-choices/v1/scan/suggestions');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) \wp_json_encode($body));

        return \rest_do_request($request);
    }
}
