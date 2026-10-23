<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\UI;

use ItalyCookieChoices\ConsentLog\UI\ConsentLogEndpoint;
use ItalyCookieChoices\Settings\Schema;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentLogEndpointTest extends WPTestCase
{
    private const ID = '3f2504e0-4f89-41d3-9a0c-0305e82c3301';
    private const PATH = '/italy-cookie-choices/v1/consents';

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
        $sut = $this->container()->get(ConsentLogEndpoint::class);

        $this->assertInstanceOf(ConsentLogEndpoint::class, $sut);
        $this->assertSame(['rest_api_init' => 'register'], \iterator_to_array($sut->getSubscribedEvents()));
    }

    public function testAVisitorLogsAChoice(): void
    {
        $response = $this->post(['id' => self::ID, 'categories' => ['necessary', 'statistics'], 'version' => '1']);

        $this->assertSame(201, $response->get_status());
        $this->assertSame([['consent_id' => self::ID, 'categories' => 'necessary statistics']], $this->rows());
    }

    public function testAWithdrawalIsLogged(): void
    {
        $response = $this->post(['id' => self::ID, 'categories' => [], 'version' => '1']);

        $this->assertSame(201, $response->get_status());
        $this->assertSame([['consent_id' => self::ID, 'categories' => '']], $this->rows());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public function invalidBodies(): iterable
    {
        yield 'missing id' => [['categories' => ['necessary'], 'version' => '1']];
        yield 'invalid id' => [['id' => 'visitor-1', 'categories' => ['necessary'], 'version' => '1']];
        yield 'categories not strings' => [['id' => self::ID, 'categories' => [['necessary']], 'version' => '1']];
        yield 'unknown category' => [['id' => self::ID, 'categories' => ['social'], 'version' => '1']];
        yield 'another version' => [['id' => self::ID, 'categories' => ['necessary'], 'version' => '2']];
        yield 'too many categories' => [['id' => self::ID, 'categories' => \range(1, 21), 'version' => '1']];
    }

    /**
     * @dataProvider invalidBodies
     * @param array<string, mixed> $body
     */
    public function testAnInvalidChoiceIsRejected(array $body): void
    {
        $response = $this->post($body);

        $this->assertSame(400, $response->get_status());
        $this->assertSame([], $this->rows());
    }

    public function testTheRouteIsMissingWhenTheLogIsOff(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $response = $this->post(['id' => self::ID, 'categories' => ['necessary'], 'version' => '1']);

        $this->assertSame(404, $response->get_status());
    }

    public function testTheRouteIsMissingWhenThePluginIsNotActive(): void
    {
        \update_option(Schema::OPTION, ['consent_log' => '1']);

        $response = $this->post(['id' => self::ID, 'categories' => ['necessary'], 'version' => '1']);

        $this->assertSame(404, $response->get_status());
    }

    /**
     * @param array<string, mixed> $body
     */
    private function post(array $body): \WP_REST_Response
    {
        unset($GLOBALS['wp_rest_server']);
        $request = new \WP_REST_Request('POST', self::PATH);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) \wp_json_encode($body));

        return \rest_do_request($request);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function rows(): array
    {
        global $wpdb;
        /** @var \wpdb $wpdb */

        return (array) $wpdb->get_results(
            'SELECT consent_id, categories FROM ' . $wpdb->prefix . 'icc_consent_log',
            \ARRAY_A
        );
    }

    private function container(): \Psr\Container\ContainerInterface
    {
        return (new \ItalyStrap\Empress\ContainerBuilder())
            ->addModule(new \ItalyStrap\Event\Module())
            ->addModule(new \ItalyCookieChoices\Consent\Module('/plugin/', 'https://example.com/plugin/', []))
            ->addModule(new \ItalyCookieChoices\ConsentLog\Module('/plugin/', 'https://example.com/plugin/'))
            ->build();
    }
}
