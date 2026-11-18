<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Multilingual;

use ItalyCookieChoices\Multilingual\Application\PolicyTranslations;
use ItalyCookieChoices\Multilingual\UI\PolicyTranslationsEndpoint;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\Multilingual\FakeLanguageTranslations;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class PolicyTranslationsEndpointTest extends WPTestCase
{
    private FakeLanguageTranslations $translations;
    private int $german = 0;

    public function setUp(): void
    {
        parent::setUp();
        $this->translations = new FakeLanguageTranslations(['it' => 'Italiano', 'de' => 'Deutsch']);
        $policy = $this->page();
        $this->german = $this->page();
        $this->translations->translatePost($policy, 'de', $this->german);
        \update_option(Schema::OPTION, ['url' => (string) \get_permalink($policy)]);

        $sut = new PolicyTranslationsEndpoint(new PolicyTranslations($this->translations));
        global $wp_rest_server;
        $wp_rest_server = new \WP_REST_Server();
        \remove_all_actions('rest_api_init');
        \add_action('rest_api_init', [$sut, 'register']);
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
        $sut = new PolicyTranslationsEndpoint(new PolicyTranslations($this->translations));

        $this->assertSame(['rest_api_init' => 'register'], \iterator_to_array($sut->getSubscribedEvents()));
    }

    public function testTheAdminUsesTheProposalOfALanguage(): void
    {
        $this->logInAs('administrator');

        $response = $this->request('de');

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $rows = $data['rows'] ?? null;
        $this->assertIsArray($rows);
        $row = $rows[0] ?? null;
        $this->assertIsArray($row);
        $this->assertSame((string) \get_permalink($this->german), $row['current'] ?? null);
    }

    public function testALanguageWithoutAProposalIsRefused(): void
    {
        $this->logInAs('administrator');

        $this->assertSame(400, $this->request('fr')->get_status());
    }

    public function testOnlyTheAdministratorsUseIt(): void
    {
        $this->logInAs('editor');

        $this->assertSame(403, $this->request('de')->get_status());
        $this->assertSame([], $this->translations->strings);
    }

    private function request(string $language): \WP_REST_Response
    {
        $request = new \WP_REST_Request('POST', '/italy-cookie-choices/v1' . PolicyTranslationsEndpoint::ROUTE);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) \wp_json_encode(['language' => $language]));

        return \rest_do_request($request);
    }

    private function page(): int
    {
        $page = self::factory()->post->create(['post_type' => 'page', 'post_status' => 'publish']);
        $this->assertIsInt($page);

        return $page;
    }

    private function logInAs(string $role): void
    {
        $userId = self::factory()->user->create(['role' => $role]);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);
    }
}
