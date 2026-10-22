<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\Registration;
use ItalyCookieChoices\Settings\Sanitizer;
use ItalyCookieChoices\Settings\Schema;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class RegistrationTest extends WPTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        \delete_option(Schema::OPTION);
    }

    public function testItRegistersOnInit(): void
    {
        $sut = new Registration(new Schema(), new Sanitizer());

        $this->assertSame(['init' => 'register'], \iterator_to_array($sut->getSubscribedEvents()));
    }

    public function testTheOptionIsRegisteredInTheSettingsPageGroup(): void
    {
        $setting = $this->registeredSetting();

        $this->assertSame(Schema::GROUP, $setting['group']);
        $this->assertSame('object', $setting['type']);
        $this->assertSame((new Schema())->defaults(), $setting['default']);
        $this->assertEquals(['schema' => (new Schema())->toJsonSchema()], $setting['show_in_rest']);
    }

    public function testTheSanitizerRunsWhenTheOptionIsSaved(): void
    {
        $this->assertInstanceOf(Sanitizer::class, $this->registeredSetting()['sanitize_callback']);
    }

    public function testTheDefaultsAreReadBeforeTheOptionExists(): void
    {
        $this->assertSame((new Schema())->defaults(), \get_option(Schema::OPTION));
    }

    public function testTheRestApiReadsTheOption(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'cookie_name' => 'name', 'cookie_value' => 'value']);
        $this->setCurrentUser('administrator');

        $data = \rest_do_request(new \WP_REST_Request('GET', '/wp/v2/settings'))->get_data();

        $this->assertIsArray($data);
        $this->assertIsArray($data[Schema::OPTION]);
        $this->assertSame('1', $data[Schema::OPTION]['active']);
    }

    public function testTheRestApiSavesAValidOptionThroughTheSanitizer(): void
    {
        $this->setCurrentUser('administrator');

        $response = $this->updateWithRest(['active' => '1', 'anchor_text' => '<b>More</b>', 'cookie_name' => 'name']);

        $this->assertSame(200, $response->get_status());
        $saved = \get_option(Schema::OPTION);
        $this->assertIsArray($saved);
        $this->assertSame('1', $saved['active']);
        $this->assertSame('More', $saved['anchor_text']);
        $this->assertSame('name', $saved['cookie_name']);
        $this->assertSame('y', $saved['cookie_value']);
    }

    public function testTheRestApiRejectsAnInvalidOption(): void
    {
        $this->setCurrentUser('administrator');

        $response = $this->updateWithRest(['banner' => '4']);

        $this->assertSame(400, $response->get_status());
        $this->assertNull(\get_option(Schema::OPTION, null));
    }

    public function testEditorsCannotSaveTheOptionWithTheRestApi(): void
    {
        $this->setCurrentUser('editor');

        $response = $this->updateWithRest(['active' => '1']);

        $this->assertSame(403, $response->get_status());
    }

    /**
     * @param array<string, mixed> $option
     */
    private function updateWithRest(array $option): \WP_REST_Response
    {
        $request = new \WP_REST_Request('POST', '/wp/v2/settings');
        $request->set_body_params([Schema::OPTION => $option]);

        return \rest_do_request($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function registeredSetting(): array
    {
        $settings = \get_registered_settings();
        $this->assertArrayHasKey(Schema::OPTION, $settings);

        return $settings[Schema::OPTION];
    }

    private function setCurrentUser(string $role): void
    {
        $userId = self::factory()->user->create(['role' => $role]);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);
    }
}
