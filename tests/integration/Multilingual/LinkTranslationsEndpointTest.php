<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Multilingual;

use ItalyCookieChoices\Multilingual\Application\LinkTranslations;
use ItalyCookieChoices\Multilingual\UI\LinkTranslationsEndpoint;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\Multilingual\FakeLanguageTranslations;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class LinkTranslationsEndpointTest extends WPTestCase
{
    private FakeLanguageTranslations $translations;
    private int $german = 0;
    private int $germanNotice = 0;

    public function setUp(): void
    {
        parent::setUp();
        $this->translations = new FakeLanguageTranslations(['it' => 'Italiano', 'de' => 'Deutsch']);
        $policy = $this->page();
        $this->german = $this->page();
        $this->translations->translatePost($policy, 'de', $this->german);
        $notice = $this->page();
        $this->germanNotice = $this->page();
        $this->translations->translatePost($notice, 'de', $this->germanNotice);
        \update_option(Schema::OPTION, [
            'url' => (string) \get_permalink($policy),
            'legal_notice_url' => (string) \get_permalink($notice),
        ]);

        $sut = new LinkTranslationsEndpoint(new LinkTranslations($this->translations));
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
        $sut = new LinkTranslationsEndpoint(new LinkTranslations($this->translations));

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

    public function testTheAdminUsesTheProposalOfTheLegalNotice(): void
    {
        $this->logInAs('administrator');

        $response = $this->request('de', 'legal-notice');

        $this->assertSame(200, $response->get_status());
        $this->assertSame(
            (string) \get_permalink($this->germanNotice),
            $this->translations->strings['Banner legal notice url:de'] ?? null
        );
        $this->assertArrayNotHasKey('Banner url:de', $this->translations->strings);
    }

    public function testAnUnknownLinkIsRefused(): void
    {
        $this->logInAs('administrator');

        $this->assertSame(400, $this->request('de', 'imprint')->get_status());
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

    private function request(string $language, string $link = 'policy'): \WP_REST_Response
    {
        $request = new \WP_REST_Request('POST', '/italy-cookie-choices/v1' . LinkTranslationsEndpoint::ROUTE);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) \wp_json_encode(['language' => $language, 'link' => $link]));

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
