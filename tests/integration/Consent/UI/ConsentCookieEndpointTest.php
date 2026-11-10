<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\UI;

use ItalyCookieChoices\Consent\Application\ConsentCookieDomain;
use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Application\RenewConsent;
use ItalyCookieChoices\Consent\Infrastructure\CookieConsentRepository;
use ItalyCookieChoices\Consent\Infrastructure\CookieConsentStorage;
use ItalyCookieChoices\Consent\UI\ConsentCookieEndpoint;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentCookieEndpointTest extends WPTestCase
{
    use BuildsContainer;
    use BuildsOptions;

    /**
     * @var list<string>
     */
    private array $sent = [];

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1']);
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
            \iterator_to_array($this->service(ConsentCookieEndpoint::class)->getSubscribedEvents())
        );
    }

    public function testItRenewsTheConsentCookie(): void
    {
        $response = $this->post($this->makeSut(true));

        $this->assertSame(204, $response->get_status());
        $this->assertSame('no-store', $response->get_headers()['Cache-Control'] ?? null);
        $this->assertSame(['icc_consent'], $this->sent);
    }

    public function testWithoutAConsentNothingIsRenewed(): void
    {
        $response = $this->post($this->makeSut(false));

        $this->assertSame(204, $response->get_status());
        $this->assertSame([], $this->sent);
    }

    public function testTheRouteIsMissingWhenTheBannerIsOff(): void
    {
        \update_option(Schema::OPTION, []);

        $this->assertSame(404, $this->post($this->makeSut(true))->get_status());
    }

    public function testTheConsentIsRenewedOnlyWithAPost(): void
    {
        $sut = $this->makeSut(true);
        $this->registerOnly($sut);

        $response = \rest_do_request(new \WP_REST_Request('GET', ConsentCookieEndpoint::PATH));

        $this->assertSame(404, $response->get_status());
        $this->assertSame([], $this->sent);
    }

    private function post(ConsentCookieEndpoint $sut): \WP_REST_Response
    {
        $this->registerOnly($sut);

        return \rest_do_request(new \WP_REST_Request('POST', ConsentCookieEndpoint::PATH));
    }

    /**
     * The endpoint of the plugin, built from the cookies of the test request, would answer first,
     * and the e2e fixtures hide the route.
     */
    private function registerOnly(ConsentCookieEndpoint $sut): void
    {
        \remove_all_actions('rest_api_init');
        \remove_all_filters('rest_endpoints');
        \add_action('rest_api_init', [$sut, 'register']);
    }

    private function makeSut(bool $withConsent): ConsentCookieEndpoint
    {
        $cookies = $withConsent
            ? ['icc_consent' => \wp_slash((string) \wp_json_encode([
                'categories' => ['necessary'],
                'version' => '1',
                'date' => (new \DateTimeImmutable('-1 day'))->format(\DateTimeInterface::RFC3339_EXTENDED),
            ]))]
            : [];

        return new ConsentCookieEndpoint(
            new RenewConsent(
                new ConsentPolicyFactory(
                    new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState()),
                    $this->options()
                ),
                new CookieConsentRepository($cookies),
                new CookieConsentStorage(
                    $cookies,
                    new ConsentCookieDomain($this->options()),
                    function (string $name): bool {
                        $this->sent[] = $name;

                        return true;
                    }
                )
            ),
            $this->options()
        );
    }
}
