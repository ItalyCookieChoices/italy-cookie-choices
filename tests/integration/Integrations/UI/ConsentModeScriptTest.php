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
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentModeScriptTest extends WPTestCase
{
    use BuildsContainer;
    use BuildsOptions;

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1', 'consent_mode' => '1']);
    }

    public function testItPrintsTheConfigurationAndTheScriptInline(): void
    {
        $sut = $this->makeSut();

        $html = $this->print($sut);

        $this->assertStringStartsWith('<script id="' . ConsentModeScript::ID . '">', $html);
        $this->assertStringContainsString(
            'window.italyCookieChoicesConsentMode = ' . \wp_json_encode($sut->config()) . ';',
            $html
        );
        $this->assertStringContainsString('/* consent-mode fixture */', $html);
    }

    public function testTheConfigurationHasTheConsentAndTheMapping(): void
    {
        $config = $this->makeSut()->config();

        $this->assertSame($this->service(ConsentScript::class)->config(), $config['consent']);
        $this->assertSame(['analytics_storage'], $config['mapping']['statistics'] ?? null);
        $this->assertSame(500, $config['waitForUpdate']);
    }

    public function testNothingIsPrintedWhenTheConsentModeIsOff(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $this->assertSame('', $this->print($this->makeSut()));
    }

    public function testNothingIsPrintedWhenTheScriptIsNotBuilt(): void
    {
        $this->assertSame('', $this->print($this->makeSut('/not/built/')));
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
            $buildPath === '' ? $fixtures : $buildPath
        );
    }
}
