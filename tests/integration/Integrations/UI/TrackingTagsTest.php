<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\UI;

use ItalyCookieChoices\Blocking\UI\BlockingFilters;
use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyCookieChoices\Integrations\UI\TrackingTags;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class TrackingTagsTest extends WPTestCase
{
    use BuildsOptions;

    private const TAGS = [
        'ga_measurement_id' => 'G-ABC123',
        'gtm_container_id' => 'GTM-ABC1',
        'facebook_pixel_id' => '123456',
    ];

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

    public function testItEnqueuesOnTheFrontEnd(): void
    {
        $this->assertSame(
            ['wp_enqueue_scripts' => 'enqueue'],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testTheTagsWaitForTheConsentToTheirCategory(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'consent_mode' => '1', ...self::TAGS]);

        $this->makeSut()->enqueue();

        $this->assertTag(
            TrackingTags::GOOGLE_ANALYTICS,
            'https://www.googletagmanager.com/gtag/js?id=G-ABC123',
            'gtag( "config", "G-ABC123" );',
            'statistics'
        );
        $this->assertTag(
            TrackingTags::GOOGLE_TAG_MANAGER,
            'https://www.googletagmanager.com/gtm.js?id=GTM-ABC1',
            'event: "gtm.js"',
            'marketing'
        );
        $this->assertTag(
            TrackingTags::FACEBOOK_PIXEL,
            'https://connect.facebook.net/en_US/fbevents.js',
            'fbq( "init", "123456" );fbq( "track", "PageView" );',
            'marketing'
        );
    }

    public function testTheGoogleTagsAreNotBlockedInTheAdvancedConsentMode(): void
    {
        \update_option(Schema::OPTION, [
            'active' => '1',
            'consent_mode' => '1',
            'consent_mode_advanced' => '1',
            ...self::TAGS,
        ]);

        $this->makeSut()->enqueue();

        $this->assertFalse(\wp_scripts()->get_data(TrackingTags::GOOGLE_ANALYTICS, BlockingFilters::SCRIPT_CATEGORY));
        $this->assertFalse(\wp_scripts()->get_data(TrackingTags::GOOGLE_TAG_MANAGER, BlockingFilters::SCRIPT_CATEGORY));
        $this->assertSame(
            'marketing',
            \wp_scripts()->get_data(TrackingTags::FACEBOOK_PIXEL, BlockingFilters::SCRIPT_CATEGORY)
        );
    }

    public function testNoTagIsEnqueuedWithoutItsId(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $this->makeSut()->enqueue();

        $this->assertFalse(\wp_script_is(TrackingTags::GOOGLE_ANALYTICS, 'registered'));
        $this->assertFalse(\wp_script_is(TrackingTags::GOOGLE_TAG_MANAGER, 'registered'));
        $this->assertFalse(\wp_script_is(TrackingTags::FACEBOOK_PIXEL, 'registered'));
    }

    private function assertTag(string $handle, string $source, string $code, string $category): void
    {
        $this->assertTrue(\wp_script_is($handle, 'enqueued'));
        $script = \wp_scripts()->registered[$handle];
        $this->assertSame($source, $script->src);
        $this->assertNull($script->ver);
        $this->assertSame('async', \wp_scripts()->get_data($handle, 'strategy'));
        $before = \wp_scripts()->get_data($handle, 'before');
        $this->assertIsArray($before);
        $this->assertStringContainsString($code, \implode('', \array_filter($before, \is_string(...))));
        $this->assertSame($category, \wp_scripts()->get_data($handle, BlockingFilters::SCRIPT_CATEGORY));
    }

    private function makeSut(): TrackingTags
    {
        return new TrackingTags(new IntegrationSettings($this->options()));
    }
}
