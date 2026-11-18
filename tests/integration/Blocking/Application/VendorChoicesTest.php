<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\Application;

use ItalyCookieChoices\Blocking\Application\VendorChoices;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class VendorChoicesTest extends WPTestCase
{
    use BuildsContainer;

    public function testTheConsentIsByCategoryByDefault(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);
        $sut = $this->service(VendorChoices::class);

        $this->assertFalse($sut->isEnabled());
        $this->assertSame([], $sut->all());
    }

    public function testItListsTheActiveVendorsWithTheirNamesAndCategories(): void
    {
        \update_option(Schema::OPTION, [
            'vendor_consent' => '1',
            'disabled_vendors' => ['hotjar'],
            'custom_vendors' => [
                [
                    'id' => 'matomo',
                    'name' => 'Matomo',
                    'category' => 'statistics',
                    'patterns' => ['matomo.example.com'],
                ],
            ],
        ]);
        $sut = $this->service(VendorChoices::class);

        $vendors = \array_column($sut->all(), null, 'id');

        $this->assertTrue($sut->isEnabled());
        $this->assertSame(
            ['id' => 'youtube', 'name' => 'YouTube', 'category' => 'marketing'],
            $vendors['youtube'] ?? null
        );
        $this->assertSame(
            ['id' => 'matomo', 'name' => 'Matomo', 'category' => 'statistics'],
            $vendors['matomo'] ?? null
        );
        $this->assertArrayNotHasKey('hotjar', $vendors);
    }
}
