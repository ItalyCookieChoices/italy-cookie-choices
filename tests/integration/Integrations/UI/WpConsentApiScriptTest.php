<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\UI;

use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\Integrations\Application\WpConsentApiMapping;
use ItalyCookieChoices\Integrations\UI\WpConsentApiScript;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use ItalyStrap\Asset\AssetLoader;
use ItalyStrap\Asset\ScriptLoader;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class WpConsentApiScriptTest extends WPTestCase
{
    use BuildsContainer;
    use BuildsOptions;

    private const BUILD_URL = 'https://example.com/wp-content/plugins/italy-cookie-choices/assets/build/';

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        \wp_register_script(ConsentScript::HANDLE, false, [], null);
        \wp_register_script(WpConsentApiScript::WP_CONSENT_API, false, [], null);
        \update_option(Schema::OPTION, ['active' => '1']);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['wp_scripts']);
        parent::tearDown();
    }

    public function testItEnqueuesAfterTheWpConsentApi(): void
    {
        $this->assertSame(
            [
                'wp_enqueue_scripts' => [
                    SubscriberInterface::CALLBACK => 'enqueue',
                    SubscriberInterface::PRIORITY => \PHP_INT_MAX - 50,
                ],
            ],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testItEnqueuesTheScriptInTheFooterAfterBothApis(): void
    {
        $this->makeSut()->enqueue();

        $script = \wp_scripts()->registered[WpConsentApiScript::HANDLE];
        $this->assertTrue(\wp_script_is(WpConsentApiScript::HANDLE, 'enqueued'));
        $this->assertSame(self::BUILD_URL . 'wp-consent-api.js', $script->src);
        $this->assertSame([WpConsentApiScript::WP_CONSENT_API, ConsentScript::HANDLE], $script->deps);
        $this->assertSame('wp-consent-api-version', $script->ver);
        $this->assertSame(1, \wp_scripts()->get_data(WpConsentApiScript::HANDLE, 'group'));
    }

    public function testItPrintsTheConfigurationBeforeTheScript(): void
    {
        $sut = $this->makeSut();
        $sut->enqueue();

        $before = \wp_scripts()->get_data(WpConsentApiScript::HANDLE, 'before');

        $this->assertIsArray($before);
        $this->assertContains(
            'window.italyCookieChoicesWpConsentApi = ' . \wp_json_encode($sut->config()) . ';',
            $before
        );
    }

    public function testTheConfigurationHasTheConsentTypeAndTheMapping(): void
    {
        $config = $this->makeSut()->config();

        $this->assertSame('optin', $config['consentType']);
        $this->assertSame(['statistics', 'statistics-anonymous'], $config['mapping']['statistics'] ?? null);
    }

    public function testNothingIsEnqueuedWithoutTheWpConsentApi(): void
    {
        \wp_deregister_script(WpConsentApiScript::WP_CONSENT_API);

        $this->makeSut()->enqueue();

        $this->assertFalse(\wp_script_is(WpConsentApiScript::HANDLE, 'registered'));
    }

    public function testNothingIsEnqueuedWhenTheBannerIsOff(): void
    {
        \update_option(Schema::OPTION, []);

        $this->makeSut()->enqueue();

        $this->assertFalse(\wp_script_is(WpConsentApiScript::HANDLE, 'registered'));
    }

    public function testNothingIsEnqueuedWhenTheScriptIsNotBuilt(): void
    {
        $this->makeSut('/not/built/')->enqueue();

        $this->assertFalse(\wp_script_is(WpConsentApiScript::HANDLE, 'registered'));
    }

    private function makeSut(string $buildPath = ''): WpConsentApiScript
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $this->assertIsString($fixtures);

        return new WpConsentApiScript(
            $this->options(),
            $this->service(WpConsentApiMapping::class),
            new AssetLoader(),
            new ScriptLoader(),
            $buildPath === '' ? $fixtures : $buildPath,
            self::BUILD_URL
        );
    }
}
