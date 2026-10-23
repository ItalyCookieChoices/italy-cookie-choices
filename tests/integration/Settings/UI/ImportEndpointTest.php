<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings\UI;

use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Settings\UI\ImportEndpoint;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ImportEndpointTest extends WPTestCase
{
    use BuildsContainer;

    private const PATH = '/italy-cookie-choices/v1/settings/import';

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1', 'text' => 'Saved']);
        $this->setCurrentUser('administrator');
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
            \iterator_to_array($this->service(ImportEndpoint::class)->getSubscribedEvents())
        );
    }

    public function testTheFileOfTheSettingsPageIsSaved(): void
    {
        $response = $this->post([
            'version' => Schema::VERSION,
            'settings' => [
                'banner' => '2',
                'button_text' => '<b>Got it</b>',
                'custom_vendors' => [
                    ['id' => 'ads', 'name' => 'Ads', 'category' => 'marketing', 'patterns' => ['ads.example.com']],
                ],
            ],
        ]);

        $this->assertSame(200, $response->get_status());
        $saved = \get_option(Schema::OPTION);
        $this->assertIsArray($saved);
        $this->assertArrayNotHasKey('active', $saved);
        $this->assertSame('2', $saved['banner']);
        $this->assertSame('Got it', $saved['button_text']);
        $this->assertSame(['ads'], \array_column($this->records($saved['custom_vendors']), 'id'));
        $this->assertSame($saved, $response->get_data());
    }

    /**
     * The 2.x export is a list with the option, it is migrated from the version 1.
     */
    public function testTheFileOfThe2xVersionsIsMigrated(): void
    {
        $response = $this->post([[
            'active' => '1',
            'scroll' => '1',
            'block_script' => ['analytics.js' => 'analytics.js'],
            'allow_iframe' => ['youtube.com/embed/public' => 'youtube.com/embed/public'],
            'cookie_name' => 'importedConsent',
        ]]);

        $this->assertSame(200, $response->get_status());
        $saved = \get_option(Schema::OPTION);
        $this->assertIsArray($saved);
        $this->assertSame('1', $saved['active']);
        $this->assertArrayNotHasKey('scroll', $saved);
        $this->assertSame('importedConsent', $saved['cookie_name']);
        $this->assertSame('1', $saved['consent_log']);
        $this->assertSame([['analytics.js']], \array_column($this->records($saved['custom_vendors']), 'patterns'));
        $this->assertSame(['youtube.com/embed/public'], $saved['allowed_patterns']);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public function invalidFiles(): iterable
    {
        yield 'empty list' => [[]];
        yield 'list without settings' => [['text']];
        yield 'object without settings' => [['version' => 5]];
        yield 'settings as a list' => [['version' => 5, 'settings' => ['a', 'b']]];
        yield 'newer version' => [['version' => Schema::VERSION + 1, 'settings' => ['active' => '1']]];
    }

    /**
     * @dataProvider invalidFiles
     */
    public function testAnInvalidFileIsRejected(mixed $file): void
    {
        $response = $this->post($file);

        $this->assertSame(400, $response->get_status());
        $this->assertSame(['active' => '1', 'text' => 'Saved'], $this->savedSubset());
    }

    public function testEditorsCannotImport(): void
    {
        $this->setCurrentUser('editor');

        $response = $this->post(['version' => 5, 'settings' => ['banner' => '2']]);

        $this->assertSame(403, $response->get_status());
        $this->assertSame(['active' => '1', 'text' => 'Saved'], $this->savedSubset());
    }

    private function post(mixed $file): \WP_REST_Response
    {
        unset($GLOBALS['wp_rest_server']);
        $request = new \WP_REST_Request('POST', self::PATH);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) \wp_json_encode(['file' => $file]));

        return \rest_do_request($request);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function records(mixed $records): array
    {
        $this->assertIsArray($records);

        return $records;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function savedSubset(): array
    {
        $saved = \get_option(Schema::OPTION);
        $this->assertIsArray($saved);

        return \array_intersect_key($saved, ['active' => true, 'text' => true]);
    }

    private function setCurrentUser(string $role): void
    {
        $userId = self::factory()->user->create(['role' => $role]);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);
    }
}
