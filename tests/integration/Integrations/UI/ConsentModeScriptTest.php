<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\UI;

use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\Integrations\Application\ConsentModeMapping;
use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyCookieChoices\Integrations\UI\ConsentModeScript;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use ItalyStrap\Asset\AssetLoader;
use ItalyStrap\Asset\ScriptLoader;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentModeScriptTest extends WPTestCase
{
    use BuildsContainer;
    use BuildsOptions;

    private const BUILD_URL = 'https://example.com/wp-content/plugins/italy-cookie-choices/assets/build/';

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        \update_option(Schema::OPTION, ['active' => '1', 'consent_mode' => '1']);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['wp_scripts']);
        parent::tearDown();
    }

    public function testItRegistersOnTheFrontEndAndPrintsAtTheStartOfTheHead(): void
    {
        $this->assertSame(
            [
                'wp_enqueue_scripts' => 'enqueue',
                'wp_head' => [SubscriberInterface::CALLBACK => 'print', SubscriberInterface::PRIORITY => 2],
            ],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testItRegistersTheBuiltScriptWithItsConfiguration(): void
    {
        $sut = $this->makeSut();

        $sut->enqueue();

        $this->assertTrue(\wp_script_is(ConsentModeScript::HANDLE, 'registered'));
        $this->assertFalse(\wp_script_is(ConsentModeScript::HANDLE, 'enqueued'));
        $script = \wp_scripts()->registered[ConsentModeScript::HANDLE];
        $this->assertSame(self::BUILD_URL . 'consent-mode.js', $script->src);
        $this->assertSame('consent-mode-version', $script->ver);
        $before = \wp_scripts()->get_data(ConsentModeScript::HANDLE, 'before');
        $this->assertIsArray($before);
        $this->assertContains(
            'window.italyCookieChoicesConsentMode = ' . \wp_json_encode($sut->config()) . ';',
            $before
        );
    }

    public function testItPrintsTheRegisteredScriptWithTheIdOfTheInlineVersion(): void
    {
        $sut = $this->makeSut();
        $sut->enqueue();

        $html = $this->print($sut);

        $this->assertStringContainsString('window.italyCookieChoicesConsentMode = ', $html);
        $processor = new \WP_HTML_Tag_Processor($html);
        $this->assertTrue($processor->next_tag(['tag_name' => 'script']));
        $this->assertTrue($processor->next_tag(['tag_name' => 'script']));
        $this->assertSame('italy-cookie-choices-consent-mode-js', $processor->get_attribute('id'));
        $this->assertSame(
            self::BUILD_URL . 'consent-mode.js?ver=consent-mode-version',
            $processor->get_attribute('src')
        );
    }

    public function testTheConfigurationHasTheConsentAndTheMapping(): void
    {
        $config = $this->makeSut()->config();

        $this->assertSame($this->service(ConsentScript::class)->config(), $config['consent']);
        $this->assertSame(['analytics_storage'], $config['mapping']['statistics'] ?? null);
        $this->assertSame(500, $config['waitForUpdate']);
    }

    public function testNothingIsRegisteredWhenTheConsentModeIsOff(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);
        $sut = $this->makeSut();

        $sut->enqueue();

        $this->assertFalse(\wp_script_is(ConsentModeScript::HANDLE, 'registered'));
        $this->assertSame('', $this->print($sut));
    }

    public function testNothingIsRegisteredWhenTheScriptIsNotBuilt(): void
    {
        $sut = $this->makeSut('/not/built/');

        $sut->enqueue();

        $this->assertFalse(\wp_script_is(ConsentModeScript::HANDLE, 'registered'));
    }

    private function print(ConsentModeScript $sut): string
    {
        \ob_start();
        $sut->print();

        return (string) \ob_get_clean();
    }

    private function makeSut(string $buildPath = ''): ConsentModeScript
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $this->assertIsString($fixtures);

        return new ConsentModeScript(
            new IntegrationSettings($this->options()),
            $this->service(ConsentModeMapping::class),
            $this->service(ConsentScript::class),
            new AssetLoader(),
            new ScriptLoader(),
            $buildPath === '' ? $fixtures : $buildPath,
            self::BUILD_URL
        );
    }
}
