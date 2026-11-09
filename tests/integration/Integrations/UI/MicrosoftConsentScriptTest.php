<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\UI;

use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyCookieChoices\Integrations\UI\MicrosoftConsentScript;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class MicrosoftConsentScriptTest extends WPTestCase
{
    use BuildsContainer;
    use BuildsOptions;

    private const UET = ['marketing' => ['ad_storage']];
    private const CLARITY = ['statistics' => ['analytics_Storage'], 'marketing' => ['ad_Storage']];

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1', 'uet_consent_mode' => '1', 'clarity_consent_mode' => '1']);
    }

    public function testItPrintsAtTheStartOfTheHead(): void
    {
        $this->assertSame(
            ['wp_head' => [SubscriberInterface::CALLBACK => 'print', SubscriberInterface::PRIORITY => 1]],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testItPrintsTheConfigurationAndTheScriptInline(): void
    {
        $sut = $this->makeSut();

        $html = $this->print($sut);

        $this->assertStringStartsWith('<script id="' . MicrosoftConsentScript::ID . '">', $html);
        $this->assertStringContainsString(
            'window.italyCookieChoicesMicrosoftConsent = ' . \wp_json_encode($sut->config()) . ';',
            $html
        );
        $this->assertStringContainsString('/* microsoft-consent fixture */', $html);
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

    public function testNothingIsPrintedWhenBothSignalsAreOff(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $this->assertSame('', $this->print($this->makeSut()));
    }

    public function testNothingIsPrintedWhenTheScriptIsNotBuilt(): void
    {
        $this->assertSame('', $this->print($this->makeSut('/not/built/')));
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
            $buildPath === '' ? $fixtures : $buildPath
        );
    }
}
