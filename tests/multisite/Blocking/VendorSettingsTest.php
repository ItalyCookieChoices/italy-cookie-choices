<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Blocking;

use ItalyCookieChoices\Blocking\Application\VendorSettings;
use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class VendorSettingsTest extends WPTestCase
{
    use BuildsContainer;

    public function tearDown(): void
    {
        \delete_site_option(NetworkSettings::OPTION);
        \restore_current_blog();
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{callable(VendorSettings): void}>
     */
    public function changes(): iterable
    {
        yield 'turn off' => [static function (VendorSettings $vendors): void {
            $vendors->disable('youtube');
        }];
        yield 'turn on' => [static function (VendorSettings $vendors): void {
            $vendors->enable('youtube');
        }];
        yield 'add' => [static function (VendorSettings $vendors): void {
            $vendors->add('Chat', 'marketing', ['a']);
        }];
    }

    /**
     * @dataProvider changes
     * @param callable(VendorSettings): void $change
     */
    public function testTheVendorsOfASiteThatUsesTheMainSiteDoNotChange(callable $change): void
    {
        (new NetworkSettings())->share(true);
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);
        \switch_to_blog($siteId);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The vendors of this site come from the main site of the network.');

        $change($this->service(VendorSettings::class));
    }
}
