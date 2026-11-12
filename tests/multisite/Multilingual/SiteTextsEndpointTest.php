<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Multilingual;

use ItalyCookieChoices\Multilingual\Application\PolicySuggestion;
use ItalyCookieChoices\Multilingual\Application\SiteTexts;
use ItalyCookieChoices\Multilingual\UI\SiteTextsEndpoint;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\Multilingual\FakeConnectedSites;
use ItalyCookieChoices\Tests\Support\Multilingual\FakeTranslator;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SiteTextsEndpointTest extends WPTestCase
{
    private int $italian = 0;
    private int $german = 0;
    private int $other = 0;
    private SiteTextsEndpoint $sut;

    public function setUp(): void
    {
        parent::setUp();
        $this->italian = $this->site(['text' => 'Usiamo i cookie', 'banner' => '2']);
        $this->german = $this->site(['banner' => '3']);
        $this->other = $this->site(['text' => 'Other']);
        $sites = new FakeConnectedSites([$this->italian => 'it-IT', $this->german => 'de-DE']);
        $this->sut = new SiteTextsEndpoint(
            new SiteTexts($sites, new FakeTranslator()),
            new PolicySuggestion($sites),
            $sites
        );

        // Only the route of the test, the one of the plugin has no connected sites without MultilingualPress.
        global $wp_rest_server;
        $wp_rest_server = new \WP_REST_Server();
        \remove_all_actions('rest_api_init');
        \add_action('rest_api_init', [$this->sut, 'register']);
        \do_action('rest_api_init', $wp_rest_server);
        \switch_to_blog($this->italian);
    }

    public function tearDown(): void
    {
        global $wp_rest_server;
        $wp_rest_server = null;
        \restore_current_blog();
        parent::tearDown();
    }

    public function testItRegistersOnTheRestApi(): void
    {
        $this->assertSame(['rest_api_init' => 'register'], \iterator_to_array($this->sut->getSubscribedEvents()));
    }

    public function testTheAdminOfBothSitesReadsAndSavesTheTextsOfAConnectedSite(): void
    {
        $this->logInAsAdminOf($this->italian, $this->german);

        $response = $this->request('GET', $this->german);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertIsArray($data['texts'] ?? null);
        $this->assertSame('', $data['texts']['text'] ?? null);
        $this->assertSame('', $data['policySuggestion'] ?? null);

        $response = $this->request('POST', $this->german, ['texts' => ['text' => 'Wir verwenden Cookies']]);
        $this->assertSame(200, $response->get_status());
        $option = \get_blog_option($this->german, Schema::OPTION);
        $this->assertIsArray($option);
        $this->assertSame('Wir verwenden Cookies', $option['text'] ?? null);
        $this->assertSame('3', $option['banner'] ?? null);
    }

    public function testItReturnsADraftFilledFromAConnectedSite(): void
    {
        $this->logInAsAdminOf($this->italian, $this->german);

        $response = $this->request('POST', $this->german, ['source' => $this->italian, 'translate' => true], '/draft');

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertIsArray($data['texts'] ?? null);
        $this->assertSame('[de-DE] Usiamo i cookie', $data['texts']['text'] ?? null);
        $option = \get_blog_option($this->german, Schema::OPTION);
        $this->assertIsArray($option);
        $this->assertArrayNotHasKey('text', $option);
    }

    public function testTheDraftExplainsWhyItFails(): void
    {
        $this->logInAsAdminOf($this->italian, $this->german, $this->other);

        $response = $this->request('POST', $this->german, ['source' => $this->other], '/draft');

        $this->assertSame(400, $response->get_status());
    }

    public function testASiteNotConnectedIsNotReachable(): void
    {
        $this->logInAsAdminOf($this->italian, $this->other);

        $this->assertSame(403, $this->request('GET', $this->other)->get_status());
    }

    public function testTheAdminOfThisSiteOnlyDoesNotReachTheConnectedSite(): void
    {
        $this->logInAsAdminOf($this->italian);

        $this->assertSame(403, $this->request('GET', $this->german)->get_status());
        $this->assertSame(403, $this->request('POST', $this->german, ['texts' => ['text' => 'No']])->get_status());
        $this->assertSame(200, $this->request('GET', $this->italian)->get_status());
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(string $method, int $siteId, array $body = [], string $suffix = ''): \WP_REST_Response
    {
        $request = new \WP_REST_Request($method, '/italy-cookie-choices/v1/sites/' . $siteId . '/texts' . $suffix);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) \wp_json_encode($body));

        return \rest_do_request($request);
    }

    private function logInAsAdminOf(int ...$siteIds): void
    {
        $userId = self::factory()->user->create(['role' => 'subscriber']);
        $this->assertIsInt($userId);
        foreach ($siteIds as $siteId) {
            \add_user_to_blog($siteId, $userId, 'administrator');
        }
        \wp_set_current_user($userId);
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
}
