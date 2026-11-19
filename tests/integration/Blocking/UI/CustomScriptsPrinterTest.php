<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\UI;

use ItalyCookieChoices\Blocking\UI\CustomScriptsPrinter;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CustomScriptsPrinterTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;

    public function setUp(): void
    {
        parent::setUp();
        // Only the users with unfiltered_html save the custom scripts.
        $this->logInAs('administrator');
    }

    private const SCRIPTS = [
        [
            'name' => 'Site',
            'category' => 'necessary',
            'position' => 'head',
            'code' => '<script>site();</script>',
        ],
        [
            'name' => 'Analytics',
            'category' => 'statistics',
            'position' => 'head',
            'code' => '<script async src="https://www.googletagmanager.com/gtag/js?id=G-ICC"></script>'
                . '<img src="https://tracker.example.com/pixel.gif" alt="">',
        ],
        [
            'name' => 'Chat',
            'category' => 'preferences',
            'position' => 'body',
            'code' => '<script>chat();</script>',
        ],
    ];

    public function testItPrintsInTheHeadAndAtTheEndOfTheBody(): void
    {
        $this->assertSame(
            ['wp_head' => 'printHead', 'wp_footer' => 'printBody'],
            \iterator_to_array($this->service(CustomScriptsPrinter::class)->getSubscribedEvents())
        );
    }

    public function testTheNecessaryScriptsArePrintedAsTheyAre(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'custom_scripts' => self::SCRIPTS]);

        $head = $this->printed(static fn(CustomScriptsPrinter $sut) => $sut->printHead());

        $this->assertStringContainsString("<script>site();</script>\n", $head);
    }

    public function testTheOtherScriptsWaitForTheConsentToTheirCategory(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'custom_scripts' => self::SCRIPTS]);

        $head = $this->printed(static fn(CustomScriptsPrinter $sut) => $sut->printHead());

        $this->assertStringContainsString(
            '<template data-icc-code data-category="statistics">'
            . '<script data-category="statistics" data-icc-vendor="google-analytics" type="text/plain" async'
            . ' src="https://www.googletagmanager.com/gtag/js?id=G-ICC"></script>'
            . '<img src="https://tracker.example.com/pixel.gif" alt=""></template>',
            $head
        );
        $this->assertStringNotContainsString('chat()', $head);
    }

    public function testTheBodyScriptsArePrintedInTheFooter(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'custom_scripts' => self::SCRIPTS]);

        $body = $this->printed(static fn(CustomScriptsPrinter $sut) => $sut->printBody());

        $this->assertStringContainsString(
            '<template data-icc-code data-category="preferences">'
            . '<script data-category="preferences" type="text/plain">chat();</script></template>',
            $body
        );
        $this->assertStringNotContainsString('site()', $body);
    }

    public function testNothingIsPrintedWhenThePluginIsNotActive(): void
    {
        \update_option(Schema::OPTION, ['custom_scripts' => self::SCRIPTS]);

        $this->assertSame('', $this->printed(static fn(CustomScriptsPrinter $sut) => $sut->printHead()));
    }

    /**
     * @param callable(CustomScriptsPrinter): void $print
     */
    private function printed(callable $print): string
    {
        $sut = $this->service(CustomScriptsPrinter::class);
        \ob_start();
        $print($sut);

        return (string) \ob_get_clean();
    }
}
