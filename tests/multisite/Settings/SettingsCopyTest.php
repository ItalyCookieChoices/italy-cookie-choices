<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Settings;

use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Settings\SettingsCopy;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SettingsCopyTest extends WPTestCase
{
    use BuildsContainer;

    private const SOURCE = [
        'active' => '1',
        'banner' => '2',
        'text' => 'The cookies of the source',
        'url' => 'https://example.com/policy/',
        'banner_logo' => '12',
        'excluded_pages' => 'contact',
        'consent_duration' => '90',
        'disabled_vendors' => ['youtube'],
        'categories' => ['statistics' => ['label' => 'Analytics']],
    ];

    private const TARGET = [
        'banner' => '3',
        'text' => 'I cookie del sito',
        'url' => 'https://example.com/it/policy/',
        'banner_logo' => '7',
        'excluded_pages' => 'contatti',
        'consent_duration' => '365',
        'categories' => ['statistics' => ['label' => 'Statistiche']],
    ];

    private int $source = 0;
    private int $target = 0;

    public function setUp(): void
    {
        parent::setUp();
        $this->source = $this->site(self::SOURCE);
        $this->target = $this->site(self::TARGET);
    }

    public function tearDown(): void
    {
        \delete_site_option(NetworkSettings::OPTION);
        parent::tearDown();
    }

    public function testItCopiesTheSettingsAndKeepsTheTextsOfTheTarget(): void
    {
        $copied = $this->sut()->copy($this->source, [$this->target], false);

        $this->assertSame([$this->target], $copied);
        $option = $this->optionOf($this->target);
        $this->assertSame('1', $option['active'] ?? null);
        $this->assertSame('2', $option['banner'] ?? null);
        $this->assertSame('90', $option['consent_duration'] ?? null);
        $this->assertSame(['youtube'], $option['disabled_vendors'] ?? null);
        $this->assertSame('I cookie del sito', $option['text'] ?? null);
        $this->assertSame('https://example.com/it/policy/', $option['url'] ?? null);
        $this->assertSame(['statistics' => ['label' => 'Statistiche']], $option['categories'] ?? null);
    }

    public function testItCopiesTheTextsToo(): void
    {
        $this->sut()->copy($this->source, [$this->target], true);

        $option = $this->optionOf($this->target);
        $this->assertSame('The cookies of the source', $option['text'] ?? null);
        $this->assertSame('https://example.com/policy/', $option['url'] ?? null);
        $this->assertSame(['statistics' => ['label' => 'Analytics']], $option['categories'] ?? null);
    }

    public function testTheTargetKeepsTheSettingsOfItsOwnContent(): void
    {
        $this->sut()->copy($this->source, [$this->target], true);

        $option = $this->optionOf($this->target);
        $this->assertSame('7', $option['banner_logo'] ?? null);
        $this->assertSame('contatti', $option['excluded_pages'] ?? null);
    }

    public function testATargetWithoutSettingsGetsThemWithoutTheSettingsOfTheSourceContent(): void
    {
        $target = $this->site(null);

        $this->sut()->copy($this->source, [$target], false);

        $option = $this->optionOf($target);
        $this->assertSame('2', $option['banner'] ?? null);
        $this->assertSame('', $option['text'] ?? null);
        $this->assertArrayNotHasKey('banner_logo', $option);
        $this->assertArrayNotHasKey('excluded_pages', $option);
    }

    public function testTheCopyIsSavedThroughTheSanitizer(): void
    {
        $source = $this->site(['text' => 'Hi<script>alert(1)</script>', 'unknown' => 'value']);

        $this->sut()->copy($source, [$this->target], true);

        $option = $this->optionOf($this->target);
        $this->assertSame('Hialert(1)', $option['text'] ?? null);
        $this->assertArrayNotHasKey('unknown', $option);
    }

    public function testTheTargetIsAtTheCurrentVersion(): void
    {
        \switch_to_blog($this->target);
        \update_option(Schema::VERSION_OPTION, 1);
        \restore_current_blog();

        $this->sut()->copy($this->source, [$this->target], false);

        $this->assertEquals(Schema::VERSION, \get_blog_option($this->target, Schema::VERSION_OPTION));
    }

    public function testTheSettingsOfASourceNotOpenedSinceAnUpdateAreMigrated(): void
    {
        \switch_to_blog($this->source);
        \delete_option(Schema::VERSION_OPTION);
        \restore_current_blog();

        $this->sut()->copy($this->source, [$this->target], false);

        $vendors = $this->optionOf($this->target)['disabled_vendors'] ?? [];
        $this->assertIsArray($vendors);
        $this->assertContains('google-recaptcha', $vendors);
    }

    public function testItSkipsTheSourceAndTheSitesThatDoNotExist(): void
    {
        $copied = $this->sut()->copy($this->source, [$this->source, 99999, $this->target], false);

        $this->assertSame([$this->target], $copied);
        $this->assertSame('The cookies of the source', $this->optionOf($this->source)['text'] ?? null);
    }

    public function testItNeedsATarget(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->sut()->copy($this->source, [$this->source, 99999], false);
    }

    public function testItNeedsAnExistingSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->sut()->copy(99999, [$this->target], false);
    }

    public function testItDoesNotCopyWhileTheSitesUseTheMainSite(): void
    {
        (new NetworkSettings())->share(true);

        try {
            $this->sut()->copy($this->source, [$this->target], false);
            $this->fail('The settings were copied.');
        } catch (\InvalidArgumentException) {
            \delete_site_option(NetworkSettings::OPTION);
            $this->assertSame('3', $this->optionOf($this->target)['banner'] ?? null);
        }
    }

    /**
     * @param array<string, mixed>|null $option Null for a site without the option.
     */
    private function site(?array $option): int
    {
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);
        \switch_to_blog($siteId);
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \delete_option(Schema::OPTION);
        if ($option !== null) {
            \update_option(Schema::OPTION, $option);
            \update_option(Schema::VERSION_OPTION, Schema::VERSION);
        }
        \restore_current_blog();

        return $siteId;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function optionOf(int $siteId): array
    {
        \switch_to_blog($siteId);
        $option = \get_option(Schema::OPTION);
        \restore_current_blog();
        $this->assertIsArray($option);

        return $option;
    }

    private function sut(): SettingsCopy
    {
        // The sanitizer of the option, removed while the sites were created.
        $this->service(\ItalyCookieChoices\Settings\Registration::class)->register();

        return $this->service(SettingsCopy::class);
    }
}
