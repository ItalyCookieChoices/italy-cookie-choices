<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\Application\ServerCookies;
use ItalyCookieChoices\Scan\UI\CookieRecorder;
use ItalyCookieChoices\Tests\Support\Scan\BuildsScanRequests;
use ItalyStrap\Asset\AssetLoader;
use ItalyStrap\Asset\ScriptLoader;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieRecorderTest extends WPTestCase
{
    use BuildsScanRequests;

    private const BUILD_URL = 'https://example.com/wp-content/plugins/italy-cookie-choices/assets/build/';

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
    }

    public function tearDown(): void
    {
        unset($GLOBALS['wp_scripts']);
        parent::tearDown();
    }

    public function testItPrintsFirstInTheHeadAndLastInTheFooter(): void
    {
        $this->assertSame(
            [
                'wp_head' => [
                    SubscriberInterface::CALLBACK => 'printRecorder',
                    SubscriberInterface::PRIORITY => \PHP_INT_MIN,
                ],
                'wp_footer' => [
                    SubscriberInterface::CALLBACK => 'printServerCookies',
                    SubscriberInterface::PRIORITY => \PHP_INT_MAX,
                ],
            ],
            \iterator_to_array($this->makeSut(isScan: true)->getSubscribedEvents())
        );
    }

    public function testAPageInScanModePrintsTheRecorder(): void
    {
        \ob_start();
        $this->makeSut(isScan: true)->printRecorder();
        $output = (string) \ob_get_clean();

        $this->assertStringContainsString(self::BUILD_URL . 'cookie-recorder.js?ver=cookie-recorder-version', $output);
        $this->assertTrue(\wp_script_is(CookieRecorder::HANDLE, 'done'));
    }

    public function testAPageInScanModePrintsTheCookiesOfTheServer(): void
    {
        $sut = $this->makeSut(isScan: true, headers: [
            'Content-Type: text/html',
            'Set-Cookie: session=abc; path=/; HttpOnly',
        ]);

        \ob_start();
        $sut->printServerCookies();
        $output = (string) \ob_get_clean();

        $this->assertStringContainsString(
            'window.italyCookieChoicesServerCookies = '
            . \wp_json_encode([['name' => 'session', 'path' => '/', 'duration' => null, 'httpOnly' => true]]) . ';',
            $output
        );
    }

    public function testAnotherPagePrintsNothing(): void
    {
        $sut = $this->makeSut(isScan: false, headers: ['Set-Cookie: session=abc']);

        \ob_start();
        $sut->printRecorder();
        $sut->printServerCookies();

        $this->assertSame('', \ob_get_clean());
        $this->assertFalse(\wp_script_is(CookieRecorder::HANDLE, 'registered'));
    }

    public function testNothingIsPrintedWhenTheAssetsAreNotBuilt(): void
    {
        \ob_start();
        $this->makeSut(isScan: true, buildPath: '/not/built/')->printRecorder();

        $this->assertSame('', \ob_get_clean());
    }

    /**
     * @param list<string> $headers
     */
    private function makeSut(bool $isScan, array $headers = [], string $buildPath = ''): CookieRecorder
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $this->assertIsString($fixtures);

        return new CookieRecorder(
            $this->scanRequest($isScan),
            new ServerCookies(),
            new AssetLoader(),
            new ScriptLoader(),
            $buildPath === '' ? $fixtures : $buildPath,
            self::BUILD_URL,
            static fn(): array => $headers
        );
    }
}
