<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\UI;

use ItalyCookieChoices\Consent\UI\Banner;
use ItalyCookieChoices\Consent\UI\BannerPreviewEndpoint;
use ItalyCookieChoices\Consent\UI\ConsentTexts;
use ItalyCookieChoices\Settings\Sanitizer;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use ItalyStrap\View\ViewInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class BannerPreviewEndpointTest extends WPTestCase
{
    use BuildsContainer;
    use BuildsOptions;

    private const PATH = '/italy-cookie-choices/v1/preview';
    private const BUILD_URL = 'https://example.com/wp-content/plugins/italy-cookie-choices/assets/build/';

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1', 'text' => 'Saved text', 'banner_bg' => '#123456']);
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
            \iterator_to_array($this->service(BannerPreviewEndpoint::class)->getSubscribedEvents())
        );
    }

    public function testThePreviewHasTheSettingsNotSavedYet(): void
    {
        $response = $this->post(['settings' => ['text' => 'Preview text', 'banner_bg' => '#abcdef']]);

        $this->assertSame(200, $response->get_status());
        $html = $this->html($response);
        $this->assertStringStartsWith('<!doctype html><html lang="en-US">', $html);
        $this->assertStringContainsString('Preview text', $html);
        $this->assertStringNotContainsString('Saved text', $html);
        $this->assertStringContainsString('--icc-background:#abcdef;', $html);
        $this->assertSame('Saved text', $this->options()->text('text'));
    }

    public function testThePreviewHasNoScript(): void
    {
        $html = $this->html($this->post(['settings' => ['text' => '<script>alert(1)</script>Text']]));

        $this->assertStringNotContainsString('<script', $html);
    }

    public function testTheBannerIsShown(): void
    {
        $processor = new \WP_HTML_Tag_Processor($this->html($this->post(['settings' => []])));

        $this->assertTrue($processor->next_tag(['tag_name' => 'div']));
        $this->assertSame('icc-banner', $processor->get_attribute('id'));
        $this->assertNull($processor->get_attribute('hidden'));
        $this->assertTrue($this->nextById($processor, 'icc-preferences'));
        $this->assertTrue($processor->get_attribute('hidden'));
    }

    public function testThePreferencesAreShown(): void
    {
        $processor = new \WP_HTML_Tag_Processor(
            $this->html($this->post(['settings' => [], 'dialog' => 'preferences']))
        );

        $this->assertTrue($this->nextById($processor, 'icc-banner'));
        $this->assertTrue($processor->get_attribute('hidden'));
        $this->assertTrue($this->nextById($processor, 'icc-preferences'));
        $this->assertNull($processor->get_attribute('hidden'));
    }

    public function testThePreviewLoadsTheStylesOfTheFrontEnd(): void
    {
        $html = $this->makeSut()->document(['js_template' => 'default'], 'icc-banner');

        $this->assertStringContainsString(
            '<link rel="stylesheet" href="' . self::BUILD_URL . 'banner.css?ver=banner-version">',
            $html
        );
    }

    public function testTheCustomTemplateHasOnlyTheCustomCss(): void
    {
        $html = $this->makeSut()->document(
            ['js_template' => 'custom', 'customCSS' => '.icc-banner{top:0}'],
            'icc-banner'
        );

        $this->assertStringNotContainsString('<link', $html);
        $this->assertStringContainsString('.icc-banner{top:0}</style>', $html);
    }

    public function testAnUnknownDialogIsRejected(): void
    {
        $this->assertSame(400, $this->post(['settings' => [], 'dialog' => 'other'])->get_status());
    }

    public function testEditorsCannotPreview(): void
    {
        $this->setCurrentUser('editor');

        $this->assertSame(403, $this->post(['settings' => []])->get_status());
    }

    private function nextById(\WP_HTML_Tag_Processor $processor, string $id): bool
    {
        while ($processor->next_tag()) {
            if ($processor->get_attribute('id') === $id) {
                return true;
            }
        }

        return false;
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

    private function html(\WP_REST_Response $response): string
    {
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertIsString($data['html']);

        return $data['html'];
    }

    private function makeSut(): BannerPreviewEndpoint
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $this->assertIsString($fixtures);

        return new BannerPreviewEndpoint(
            new Banner(
                $this->service(ConsentTexts::class),
                $this->options(),
                $this->service(ViewInterface::class),
                $fixtures,
                self::BUILD_URL
            ),
            new Sanitizer()
        );
    }

    private function setCurrentUser(string $role): void
    {
        $userId = self::factory()->user->create(['role' => $role]);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);
    }
}
