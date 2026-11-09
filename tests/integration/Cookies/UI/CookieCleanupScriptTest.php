<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Cookies\UI;

use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\Cookies\Application\CookieTableFactory;
use ItalyCookieChoices\Cookies\UI\CookieCleanupScript;
use ItalyCookieChoices\Blocking\UI\BlockableRequest;
use ItalyCookieChoices\Consent\UI\ConsentTexts;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyStrap\Asset\AssetLoader;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieCleanupScriptTest extends WPTestCase
{
    use BuildsContainer;

    private const BUILD_URL = 'https://example.com/wp-content/plugins/italy-cookie-choices/assets/build/';

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        \wp_register_script(ConsentScript::HANDLE, false, [], null);
        \update_option(Schema::OPTION, ['active' => '1']);
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

    public function testItEnqueuesTheScriptInTheFooterAfterTheConsentApi(): void
    {
        $sut = $this->makeSut();

        $sut->enqueue();

        $script = \wp_scripts()->registered[CookieCleanupScript::HANDLE];
        $this->assertTrue(\wp_script_is(CookieCleanupScript::HANDLE, 'enqueued'));
        $this->assertSame(self::BUILD_URL . 'cookie-cleanup.js', $script->src);
        $this->assertSame([ConsentScript::HANDLE], $script->deps);
        $this->assertSame(1, \wp_scripts()->get_data(CookieCleanupScript::HANDLE, 'group'));
        $before = \wp_scripts()->get_data(CookieCleanupScript::HANDLE, 'before');
        $this->assertIsArray($before);
        $this->assertContains(
            'window.italyCookieChoicesCookieCleanup = ' . \wp_json_encode($sut->config()) . ';',
            $before
        );
    }

    public function testTheConfigurationListsTheCookiesOfTheOptionalCategories(): void
    {
        $categories = $this->makeSut()->config()['categories'];

        $this->assertContains('_ga', $categories['statistics'] ?? []);
        $this->assertContains('_ga_*', $categories['statistics'] ?? []);
        $this->assertContains('_fbp', $categories['marketing'] ?? []);
        $this->assertArrayNotHasKey('necessary', $categories);
    }

    public function testNothingIsEnqueuedWhenThePluginIsNotActive(): void
    {
        \update_option(Schema::OPTION, []);

        $this->makeSut()->enqueue();

        $this->assertFalse(\wp_script_is(CookieCleanupScript::HANDLE, 'registered'));
    }

    public function testNothingIsEnqueuedWhenTheScriptIsNotBuilt(): void
    {
        $this->makeSut('/not/built/')->enqueue();

        $this->assertFalse(\wp_script_is(CookieCleanupScript::HANDLE, 'registered'));
    }

    private function makeSut(string $buildPath = ''): CookieCleanupScript
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $this->assertIsString($fixtures);

        return new CookieCleanupScript(
            $this->service(CookieTableFactory::class),
            $this->service(ConsentTexts::class),
            $this->service(BlockableRequest::class),
            new AssetLoader(),
            $buildPath === '' ? $fixtures : $buildPath,
            self::BUILD_URL
        );
    }
}
