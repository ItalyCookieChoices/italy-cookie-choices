<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\UI;

use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyCookieChoices\Integrations\UI\MicrosoftConsentScript;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use ItalyStrap\Asset\AssetLoader;
use ItalyStrap\Asset\ScriptLoader;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class MicrosoftConsentScriptTest extends WPTestCase
{
    use BuildsContainer;
    use BuildsOptions;

    private const BUILD_URL = 'https://example.com/wp-content/plugins/italy-cookie-choices/assets/build/';
    private const UET = ['marketing' => ['ad_storage']];
    private const CLARITY = ['statistics' => ['analytics_Storage'], 'marketing' => ['ad_Storage']];

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        \update_option(Schema::OPTION, ['active' => '1', 'uet_consent_mode' => '1', 'clarity_consent_mode' => '1']);
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

        $this->assertTrue(\wp_script_is(MicrosoftConsentScript::HANDLE, 'registered'));
        $this->assertFalse(\wp_script_is(MicrosoftConsentScript::HANDLE, 'enqueued'));
        $script = \wp_scripts()->registered[MicrosoftConsentScript::HANDLE];
        $this->assertSame(self::BUILD_URL . 'microsoft-consent.js', $script->src);
        $this->assertSame('microsoft-consent-version', $script->ver);
        $before = \wp_scripts()->get_data(MicrosoftConsentScript::HANDLE, 'before');
        $this->assertIsArray($before);
        $this->assertContains(
            'window.italyCookieChoicesMicrosoftConsent = ' . \wp_json_encode($sut->config()) . ';',
            $before
        );
    }

    public function testItPrintsTheRegisteredScript(): void
    {
        $sut = $this->makeSut();
        $sut->enqueue();

        $html = $this->print($sut);

        $this->assertStringContainsString('window.italyCookieChoicesMicrosoftConsent = ', $html);
        $this->assertStringContainsString(self::BUILD_URL . 'microsoft-consent.js', $html);
    }

    public function testTheConfigurationHasTheConsentAndTheMappings(): void
    {
        $config = $this->makeSut()->config();

        $this->assertSame($this->service(ConsentScript::class)->config(), $config['consent']);
        $this->assertSame(self::UET, $config['uet']);
        $this->assertSame(self::CLARITY, $config['clarity']);
    }

    public function testASignalTurnedOffHasNoMapping(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'clarity_consent_mode' => '1']);

        $config = $this->makeSut()->config();

        $this->assertNull($config['uet']);
        $this->assertSame(self::CLARITY, $config['clarity']);
    }

    public function testNothingIsRegisteredWhenBothSignalsAreOff(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);
        $sut = $this->makeSut();

        $sut->enqueue();

        $this->assertFalse(\wp_script_is(MicrosoftConsentScript::HANDLE, 'registered'));
        $this->assertSame('', $this->print($sut));
    }

    public function testNothingIsRegisteredWhenTheScriptIsNotBuilt(): void
    {
        $sut = $this->makeSut('/not/built/');

        $sut->enqueue();

        $this->assertFalse(\wp_script_is(MicrosoftConsentScript::HANDLE, 'registered'));
    }

    private function print(MicrosoftConsentScript $sut): string
    {
        \ob_start();
        $sut->print();

        return (string) \ob_get_clean();
    }

    private function makeSut(string $buildPath = ''): MicrosoftConsentScript
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $this->assertIsString($fixtures);

        return new MicrosoftConsentScript(
            new IntegrationSettings($this->options()),
            $this->service(ConsentScript::class),
            new AssetLoader(),
            new ScriptLoader(),
            $buildPath === '' ? $fixtures : $buildPath,
            self::BUILD_URL
        );
    }
}
