<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\Application;

use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class IntegrationSettingsTest extends WPTestCase
{
    use BuildsOptions;

    public function testTheSavedSettingsAreRead(): void
    {
        \update_option(Schema::OPTION, [
            'active' => '1',
            'consent_mode' => '1',
            'consent_mode_advanced' => '1',
            'ga_measurement_id' => 'G-ABC123',
            'gtm_container_id' => 'GTM-ABC1',
            'facebook_pixel_id' => '123456',
            'youtube_nocookie' => '1',
        ]);

        $sut = new IntegrationSettings($this->options());

        $this->assertTrue($sut->sendsConsentMode());
        $this->assertTrue($sut->isConsentModeAdvanced());
        $this->assertSame('G-ABC123', $sut->measurementId());
        $this->assertSame('GTM-ABC1', $sut->containerId());
        $this->assertSame('123456', $sut->pixelId());
        $this->assertTrue($sut->usesYouTubeNoCookie());
    }

    public function testTheAdvancedModeNeedsTheConsentMode(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'consent_mode_advanced' => '1']);

        $sut = new IntegrationSettings($this->options());

        $this->assertFalse($sut->sendsConsentMode());
        $this->assertFalse($sut->isConsentModeAdvanced());
    }

    public function testNothingIsOnWhenThePluginIsNotActive(): void
    {
        \update_option(Schema::OPTION, [
            'consent_mode' => '1',
            'ga_measurement_id' => 'G-ABC123',
            'youtube_nocookie' => '1',
        ]);

        $sut = new IntegrationSettings($this->options());

        $this->assertFalse($sut->sendsConsentMode());
        $this->assertSame('', $sut->measurementId());
        $this->assertFalse($sut->usesYouTubeNoCookie());
    }
}
