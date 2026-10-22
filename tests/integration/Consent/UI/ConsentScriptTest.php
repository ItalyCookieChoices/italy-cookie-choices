<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\UI;

use ItalyCookieChoices\Consent\Application\BuildingConsentPolicy;
use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use ItalyStrap\Event\SubscriberInterface;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentScriptTest extends WPTestCase
{
    private const BUILD_URL = 'https://example.com/wp-content/plugins/italy-cookie-choices/assets/build/';

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        \update_option(Schema::OPTION, ['active' => '1', 'cookie_name' => 'accepted', 'cookie_value' => 'yes']);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['wp_scripts']);
        parent::tearDown();
    }

    public function testItEnqueuesOnTheFrontEnd(): void
    {
        $this->assertSame(
            [
                'wp_enqueue_scripts' => 'enqueue',
                'wp_head' => [SubscriberInterface::CALLBACK => 'print', SubscriberInterface::PRIORITY => 100000],
            ],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testItRegistersTheBuiltScriptInTheHead(): void
    {
        $this->makeSut()->enqueue();

        $this->assertTrue(\wp_script_is(ConsentScript::HANDLE, 'registered'));
        $script = \wp_scripts()->registered[ConsentScript::HANDLE];
        $this->assertSame(self::BUILD_URL . 'consent.js', $script->src);
        $this->assertSame(['wp-dom-ready'], $script->deps);
        $this->assertSame('test-version', $script->ver);
        $this->assertNotSame(1, \wp_scripts()->get_data(ConsentScript::HANDLE, 'group'));
    }

    public function testItPrintsTheConfigurationBeforeTheScript(): void
    {
        $sut = $this->makeSut();

        $sut->enqueue();

        $before = \wp_scripts()->get_data(ConsentScript::HANDLE, 'before');
        $this->assertIsArray($before);
        $this->assertSame(
            ['window.italyCookieChoicesConfig = ' . \wp_json_encode($sut->config()) . ';'],
            \array_values(\array_filter($before))
        );
    }

    public function testTheConfigurationDescribesThePolicy(): void
    {
        $this->assertSame(
            [
                'cookieName' => 'icc_consent',
                'categories' => [
                    ['id' => 'necessary', 'required' => true],
                    ['id' => 'preferences', 'required' => false],
                    ['id' => 'statistics', 'required' => false],
                    ['id' => 'marketing', 'required' => false],
                ],
                'version' => '1',
                'durationDays' => 182,
                'legacyCookie' => ['name' => 'accepted', 'value' => 'yes'],
            ],
            $this->makeSut()->config()
        );
    }

    public function testTheConfigurationFollowsThePolicyListeners(): void
    {
        \add_action(BuildingConsentPolicy::class, static function (BuildingConsentPolicy $event): void {
            $event->changeVersion(new PolicyVersion('2'));
        });

        $this->assertSame('2', $this->makeSut()->config()['version']);
    }

    public function testItPrintsTheScriptWithItsConfiguration(): void
    {
        $sut = $this->makeSut();
        $sut->enqueue();

        \ob_start();
        $sut->print();
        $output = (string) \ob_get_clean();

        $this->assertStringContainsString('window.italyCookieChoicesConfig = ', $output);
        $this->assertStringContainsString(self::BUILD_URL . 'consent.js?ver=test-version', $output);
        $this->assertTrue(\wp_script_is(ConsentScript::HANDLE, 'done'));
    }

    public function testNothingIsPrintedWhenTheScriptIsNotRegistered(): void
    {
        \ob_start();
        $this->makeSut()->print();

        $this->assertSame('', \ob_get_clean());
    }

    public function testNothingIsEnqueuedWhenThePluginIsNotActive(): void
    {
        \update_option(Schema::OPTION, ['cookie_name' => 'accepted']);

        $this->makeSut()->enqueue();

        $this->assertFalse(\wp_script_is(ConsentScript::HANDLE, 'registered'));
    }

    public function testNothingIsEnqueuedWhenTheAssetsAreNotBuilt(): void
    {
        $this->makeSut('/not/built/')->enqueue();

        $this->assertFalse(\wp_script_is(ConsentScript::HANDLE, 'registered'));
    }

    private function makeSut(string $buildPath = ''): ConsentScript
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $this->assertIsString($fixtures);

        return new ConsentScript(
            new ConsentPolicyFactory($this->dispatcher()),
            new Options(),
            $buildPath === '' ? $fixtures : $buildPath,
            self::BUILD_URL
        );
    }

    private function dispatcher(): Dispatcher
    {
        return new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState());
    }
}
