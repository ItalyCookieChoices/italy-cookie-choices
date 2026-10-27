<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\UI;

use ItalyCookieChoices\Blocking\Application\BuildingVendors;
use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyCookieChoices\Integrations\UI\AdvancedConsentMode;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class AdvancedConsentModeTest extends WPTestCase
{
    use BuildsOptions;

    public function testItListensToTheVendors(): void
    {
        $this->assertSame(
            [BuildingVendors::class => 'unblockGoogleTags'],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testTheGoogleTagsAreNotBlockedInTheAdvancedMode(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'consent_mode' => '1', 'consent_mode_advanced' => '1']);

        $ids = $this->vendorIds();

        $this->assertNotContains('google-analytics', $ids);
        $this->assertNotContains('google-tag-manager', $ids);
        $this->assertContains('youtube', $ids);
    }

    public function testTheGoogleTagsAreBlockedInTheBasicMode(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'consent_mode' => '1']);

        $this->assertContains('google-analytics', $this->vendorIds());
    }

    /**
     * @return list<string>
     */
    private function vendorIds(): array
    {
        $event = new BuildingVendors((new VendorPresets())->all());

        $this->makeSut()->unblockGoogleTags($event);

        return \array_values(\array_map(
            static fn(Vendor $vendor): string => $vendor->id(),
            \iterator_to_array($event->vendors())
        ));
    }

    private function makeSut(): AdvancedConsentMode
    {
        return new AdvancedConsentMode(new IntegrationSettings($this->options()));
    }
}
