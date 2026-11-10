<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Settings;

use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Settings\SharedSettings;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SharedSettingsTest extends WPTestCase
{
    use BuildsContainer;

    private const MAIN = [
        'active' => '1',
        'banner' => '2',
        'text' => 'The cookies of the network',
        'url' => 'https://example.com/policy/',
        'button_text' => 'Accept',
        'disabled_vendors' => ['youtube'],
        'categories' => ['statistics' => ['label' => 'Analytics']],
    ];

    private const SITE = [
        'banner' => '3',
        'text' => 'I cookie del sito',
        'url' => 'https://example.com/it/policy/',
        'floating_button_label' => 'Preferenze',
        'categories' => ['statistics' => ['label' => 'Statistiche']],
    ];

    private int $siteId = 0;

    public function setUp(): void
    {
        parent::setUp();
        $this->savedWithoutTheSanitizer(self::MAIN);
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);
        $this->siteId = $siteId;
        \switch_to_blog($this->siteId);
        $this->savedWithoutTheSanitizer(self::SITE);
        \restore_current_blog();
    }

    public function tearDown(): void
    {
        \delete_site_option(NetworkSettings::OPTION);
        parent::tearDown();
    }

    public function testItFiltersTheOptionAndItsDefault(): void
    {
        $this->assertSame(
            [
                'option_' . Schema::OPTION => [SharedSettings::CALLBACK => 'merge', SharedSettings::PRIORITY => 20],
                'default_option_' . Schema::OPTION => [
                    SharedSettings::CALLBACK => 'merge',
                    SharedSettings::PRIORITY => 20,
                ],
            ],
            \iterator_to_array($this->service(SharedSettings::class)->getSubscribedEvents())
        );
    }

    public function testEverySiteReadsItsOwnSettingsByDefault(): void
    {
        \switch_to_blog($this->siteId);

        $this->assertSame(self::SITE, \get_option(Schema::OPTION));
        \restore_current_blog();
    }

    public function testTheOtherSitesReadTheMainSiteWithTheirOwnTexts(): void
    {
        (new NetworkSettings())->share(true);
        \switch_to_blog($this->siteId);

        $this->assertSame(
            [
                'active' => '1',
                'banner' => '2',
                'disabled_vendors' => ['youtube'],
                'text' => 'I cookie del sito',
                'url' => 'https://example.com/it/policy/',
                'floating_button_label' => 'Preferenze',
                'categories' => ['statistics' => ['label' => 'Statistiche']],
            ],
            \get_option(Schema::OPTION)
        );
        $options = $this->service(Options::class);
        $this->assertTrue($options->isActive());
        $this->assertSame('', $options->text('button_text'));
        \restore_current_blog();
    }

    public function testTheMainSiteReadsItsOwnSettings(): void
    {
        (new NetworkSettings())->share(true);

        $this->assertSame(self::MAIN, \get_option(Schema::OPTION));
    }

    public function testASiteWithoutSettingsReadsTheMainSiteWithoutItsTexts(): void
    {
        (new NetworkSettings())->share(true);
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);
        \switch_to_blog($siteId);
        \delete_option(Schema::OPTION);

        // The texts are the defaults of the site.
        $this->assertSame(
            [
                'active' => '1',
                'banner' => '2',
                'disabled_vendors' => ['youtube'],
                'text' => '',
                'url' => '',
                'anchor_text' => '',
                'button_text' => '',
                'content_message_text' => '',
                'content_message_button_text' => '',
            ],
            \get_option(Schema::OPTION)
        );
        \restore_current_blog();
    }

    /**
     * @param array<string, mixed> $option
     */
    private function savedWithoutTheSanitizer(array $option): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(Schema::OPTION, $option);
    }
}
