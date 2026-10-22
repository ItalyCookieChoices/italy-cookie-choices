<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\Migration;
use ItalyCookieChoices\Settings\Schema;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class MigrationTest extends WPTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        \delete_option(Schema::OPTION);
        \delete_option(Schema::VERSION_OPTION);
        // The migration runs on plugins_loaded, before the option and its sanitizer are registered on init
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
    }

    public function testItMigratesBeforeTheLegacyCodeIsLoaded(): void
    {
        $sut = new Migration(new Schema());

        $this->assertSame(['plugins_loaded' => 'migrate'], \iterator_to_array($sut->getSubscribedEvents()));
        $this->assertSame(10, \has_action('plugins_loaded', [$this->registeredMigration(), 'migrate']));
    }

    public function testANewInstallationGetsTheDefaults(): void
    {
        (new Migration(new Schema()))->migrate();

        $this->assertSame((new Schema())->defaults(), \get_option(Schema::OPTION, null));
        $this->assertSame(2, \get_option(Schema::VERSION_OPTION));
    }

    public function testTheDefaultsAreStoredWithoutTheSanitizer(): void
    {
        (new Migration(new Schema()))->migrate();

        $stored = \get_option(Schema::OPTION, null);
        $this->assertIsArray($stored);
        $this->assertArrayNotHasKey('banner_bg', $stored);
    }

    /**
     * The 2.x values have the shape of the schema version 1, they are kept as they are.
     */
    public function testTheOptionOfThe2xVersionsIsKept(): void
    {
        $legacy = [
            'active' => '1',
            'banner' => '3',
            'text' => 'Policy',
            'cookie_name' => 'custom',
            'cookie_value' => 'ok',
            'block_script' => ['analytics.js' => 'analytics.js'],
        ];
        \add_option(Schema::OPTION, $legacy);

        (new Migration(new Schema()))->migrate();

        $this->assertSame($legacy, \get_option(Schema::OPTION));
        $this->assertSame(Schema::VERSION, \get_option(Schema::VERSION_OPTION));
    }

    /**
     * Version 2: accepting on scroll and on the second page view is not a valid consent under the GDPR.
     */
    public function testTheScrollAndTheSecondViewOfThe2xVersionsAreRemoved(): void
    {
        \add_option(Schema::OPTION, [
            'active' => '1',
            'scroll' => '1',
            'secondView' => '1',
            'text' => 'Policy',
            'block_script' => ['analytics.js' => 'analytics.js'],
        ]);

        (new Migration(new Schema()))->migrate();

        $this->assertSame(
            [
                'active' => '1',
                'text' => 'Policy',
                'block_script' => ['analytics.js' => 'analytics.js'],
            ],
            \get_option(Schema::OPTION)
        );
        $this->assertSame(2, \get_option(Schema::VERSION_OPTION));
    }

    public function testTheRemovedValuesAreStoredWithoutTheSanitizer(): void
    {
        \add_option(Schema::OPTION, ['scroll' => '1', 'text' => '<script>raw</script>']);

        (new Migration(new Schema()))->migrate();

        $this->assertSame(['text' => '<script>raw</script>'], \get_option(Schema::OPTION));
    }

    public function testAnOptionAtVersion1RunsOnlyTheVersion2(): void
    {
        \update_option(Schema::VERSION_OPTION, 1);
        \add_option(Schema::OPTION, ['secondView' => '1', 'active' => '1']);

        (new Migration(new Schema()))->migrate();

        $this->assertSame(['active' => '1'], \get_option(Schema::OPTION));
        $this->assertSame(2, \get_option(Schema::VERSION_OPTION));
    }

    public function testAMissingOptionAtVersion1IsNotCreated(): void
    {
        \update_option(Schema::VERSION_OPTION, 1);

        (new Migration(new Schema()))->migrate();

        $this->assertNull(\get_option(Schema::OPTION, null));
        $this->assertSame(2, \get_option(Schema::VERSION_OPTION));
    }

    public function testNothingRunsWhenTheVersionIsCurrent(): void
    {
        \update_option(Schema::VERSION_OPTION, Schema::VERSION);

        (new Migration(new Schema()))->migrate();

        $this->assertNull(\get_option(Schema::OPTION, null));
    }

    public function testAStoredVersionAsStringIsRead(): void
    {
        \update_option(Schema::VERSION_OPTION, (string) Schema::VERSION);

        (new Migration(new Schema()))->migrate();

        $this->assertNull(\get_option(Schema::OPTION, null));
    }

    public function testAnInvalidStoredVersionRunsTheMigration(): void
    {
        \update_option(Schema::VERSION_OPTION, 'invalid');

        (new Migration(new Schema()))->migrate();

        $this->assertSame((new Schema())->defaults(), \get_option(Schema::OPTION, null));
    }

    private function registeredMigration(): Migration
    {
        /** @var array<string, \WP_Hook> $wp_filter */
        global $wp_filter;

        foreach ($wp_filter['plugins_loaded']->callbacks as $callbacks) {
            foreach ($callbacks as $callback) {
                if (\is_array($callback['function']) && $callback['function'][0] instanceof Migration) {
                    return $callback['function'][0];
                }
            }
        }

        $this->fail('The migration is not hooked on plugins_loaded');
    }
}
