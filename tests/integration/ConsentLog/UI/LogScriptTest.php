<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\UI;

use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\ConsentLog\Application\LogSettings;
use ItalyCookieChoices\ConsentLog\UI\LogScript;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class LogScriptTest extends WPTestCase
{
    private const BUILD_URL = 'https://example.com/wp-content/plugins/italy-cookie-choices/assets/build/';

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        \wp_register_script(ConsentScript::HANDLE, false, [], null);
        \update_option(Schema::OPTION, ['active' => '1', 'consent_log' => '1']);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['wp_scripts']);
        parent::tearDown();
    }

    public function testItEnqueuesOnTheFrontEnd(): void
    {
        $this->assertSame(
            ['wp_enqueue_scripts' => 'enqueue'],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testItEnqueuesTheScriptWithTheEndpoint(): void
    {
        $sut = $this->makeSut();

        $sut->enqueue();

        $script = \wp_scripts()->registered[LogScript::HANDLE];
        $this->assertTrue(\wp_script_is(LogScript::HANDLE, 'enqueued'));
        $this->assertSame(self::BUILD_URL . 'consent-log.js', $script->src);
        $this->assertSame([ConsentScript::HANDLE], $script->deps);
        $this->assertSame(1, \wp_scripts()->get_data(LogScript::HANDLE, 'group'));
        $this->assertStringEndsWith('/italy-cookie-choices/v1/consents', $sut->url());
        $this->assertSame(
            ['window.italyCookieChoicesLog = ' . \wp_json_encode(['url' => $sut->url()]) . ';'],
            \array_values(\array_filter((array) \wp_scripts()->get_data(LogScript::HANDLE, 'before')))
        );
    }

    public function testNothingIsEnqueuedWhenTheLogIsOff(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $this->makeSut()->enqueue();

        $this->assertFalse(\wp_script_is(LogScript::HANDLE, 'registered'));
    }

    public function testNothingIsEnqueuedWhenTheAssetsAreNotBuilt(): void
    {
        $this->makeSut('/not/built/')->enqueue();

        $this->assertFalse(\wp_script_is(LogScript::HANDLE, 'registered'));
    }

    private function makeSut(string $buildPath = ''): LogScript
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $this->assertIsString($fixtures);

        return new LogScript(
            new LogSettings(new Options()),
            $buildPath === '' ? $fixtures : $buildPath,
            self::BUILD_URL
        );
    }
}
