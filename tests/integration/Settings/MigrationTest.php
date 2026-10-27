<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\LegacySnippets;
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
        $sut = new Migration(new Schema(), new LegacySnippets());

        $this->assertSame(['plugins_loaded' => 'migrate'], \iterator_to_array($sut->getSubscribedEvents()));
        $this->assertSame(10, \has_action('plugins_loaded', [$this->registeredMigration(), 'migrate']));
    }

    public function testANewInstallationGetsTheDefaults(): void
    {
        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame((new Schema())->defaults(), \get_option(Schema::OPTION, null));
        $this->assertSame(Schema::VERSION, \get_option(Schema::VERSION_OPTION));
    }

    public function testTheDefaultsAreStoredWithoutTheSanitizer(): void
    {
        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $stored = \get_option(Schema::OPTION, null);
        $this->assertIsArray($stored);
        $this->assertArrayNotHasKey('banner_bg', $stored);
    }

    /**
     * The 2.x values have the shape of the schema version 1, they are kept as they are,
     * the consent log is added and the lists move to the vendors.
     */
    public function testTheOptionOfThe2xVersionsIsKeptWithTheConsentLog(): void
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

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame(
            [
                'active' => '1',
                'banner' => '3',
                'text' => 'Policy',
                'cookie_name' => 'custom',
                'cookie_value' => 'ok',
                ...Schema::CONSENT_LOG_DEFAULTS,
                'custom_vendors' => [$this->customVendor(['analytics.js'])],
                ...Schema::CONSENT_MODE_DEFAULTS,
            ],
            \get_option(Schema::OPTION)
        );
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

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame(
            [
                'active' => '1',
                'text' => 'Policy',
                ...Schema::CONSENT_LOG_DEFAULTS,
                'custom_vendors' => [$this->customVendor(['analytics.js'])],
                ...Schema::CONSENT_MODE_DEFAULTS,
            ],
            \get_option(Schema::OPTION)
        );
        $this->assertSame(Schema::VERSION, \get_option(Schema::VERSION_OPTION));
    }

    public function testTheRemovedValuesAreStoredWithoutTheSanitizer(): void
    {
        \add_option(Schema::OPTION, ['scroll' => '1', 'text' => '<script>raw</script>']);

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame(
            ['text' => '<script>raw</script>'] + Schema::CONSENT_LOG_DEFAULTS + Schema::CONSENT_MODE_DEFAULTS,
            \get_option(Schema::OPTION)
        );
    }

    public function testAnOptionAtVersion1RunsOnlyTheNextVersions(): void
    {
        \update_option(Schema::VERSION_OPTION, 1);
        \add_option(Schema::OPTION, ['secondView' => '1', 'active' => '1']);

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame(
            ['active' => '1'] + Schema::CONSENT_LOG_DEFAULTS + Schema::CONSENT_MODE_DEFAULTS,
            \get_option(Schema::OPTION)
        );
        $this->assertSame(Schema::VERSION, \get_option(Schema::VERSION_OPTION));
    }

    /**
     * Version 3: the vendors are always blocked, the snippets of the 2.x textareas move to the lists.
     */
    public function testTheSnippetsOfThe2xTextareasMoveToTheLists(): void
    {
        \update_option(Schema::VERSION_OPTION, 2);
        \add_option(Schema::OPTION, [
            'active' => '1',
            'block' => '1',
            'widget_block' => '1',
            'all_block' => '1',
            'block_script' => ['analytics.js' => 'analytics.js'],
            'custom_script_block' => '<script src="https://tracker.example.com/t.js?id=1"></script>'
                . "\n<---------SEP--------->\n"
                . '<script>window.tracker = true;</script>',
            'custom_script_block_body_exclude' => '<script>window.allowed<---------SOMETHING---------></script>',
        ]);

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame(
            [
                'active' => '1',
                'all_block' => '1',
                ...Schema::CONSENT_LOG_DEFAULTS,
                'custom_vendors' => [
                    $this->customVendor(['analytics.js', 'tracker.example.com/t.js', 'window.tracker = true;']),
                ],
                'allowed_patterns' => ['window.allowed'],
                ...Schema::CONSENT_MODE_DEFAULTS,
            ],
            \get_option(Schema::OPTION)
        );
    }

    public function testEmptyTextareasAreRemoved(): void
    {
        \update_option(Schema::VERSION_OPTION, 2);
        \add_option(Schema::OPTION, [
            'active' => '1',
            'custom_script_block' => '',
            'custom_script_block_body_exclude' => ['a'],
        ]);

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame(
            ['active' => '1'] + Schema::CONSENT_LOG_DEFAULTS + Schema::CONSENT_MODE_DEFAULTS,
            \get_option(Schema::OPTION)
        );
    }

    public function testAnOptionWithoutTheTextareasIsKeptWithoutTheInvalidLists(): void
    {
        \update_option(Schema::VERSION_OPTION, 2);
        \add_option(Schema::OPTION, ['active' => '1', 'block_script' => 'invalid']);

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame(
            ['active' => '1'] + Schema::CONSENT_LOG_DEFAULTS + Schema::CONSENT_MODE_DEFAULTS,
            \get_option(Schema::OPTION)
        );
        $this->assertSame(Schema::VERSION, \get_option(Schema::VERSION_OPTION));
    }

    /**
     * Version 4: the consent log is on with a 12 months retention, a saved choice is kept.
     */
    public function testTheConsentLogIsTurnedOn(): void
    {
        \update_option(Schema::VERSION_OPTION, 3);
        \add_option(Schema::OPTION, ['active' => '1', 'consent_log_retention' => '24']);

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame(
            ['active' => '1', 'consent_log_retention' => '24', 'consent_log' => '1', 'consent_mode' => '1'],
            \get_option(Schema::OPTION)
        );
    }

    /**
     * Version 5: the lists of the 2.x versions become a custom marketing vendor and the allowed patterns.
     */
    public function testTheListsMoveToTheVendors(): void
    {
        \update_option(Schema::VERSION_OPTION, 4);
        \add_option(Schema::OPTION, [
            'active' => '1',
            'block_script' => ['analytics.js' => 'analytics.js', 'empty' => ' '],
            'block_iframe' => ['maps.example.com' => 'maps.example.com', 'analytics.js' => 'analytics.js'],
            'block_embed' => [],
            'allow_script' => ['window.allowed' => 'window.allowed'],
            'allow_iframe' => ['youtube.com/embed/public' => 'youtube.com/embed/public'],
            'allow_embed' => ['invalid' => ['x']],
        ]);

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame(
            [
                'active' => '1',
                'custom_vendors' => [$this->customVendor(['analytics.js', 'maps.example.com'])],
                'allowed_patterns' => ['window.allowed', 'youtube.com/embed/public'],
                ...Schema::CONSENT_MODE_DEFAULTS,
            ],
            \get_option(Schema::OPTION)
        );
    }

    /**
     * Version 5: the REST API reads null for an option with a value out of the schema.
     */
    public function testTheValuesOutOfTheSchemaAreRemoved(): void
    {
        \update_option(Schema::VERSION_OPTION, 4);
        \add_option(Schema::OPTION, [
            'active' => 'on',
            'banner' => '2',
            'unknown' => '1',
            'js_template' => 'removed',
            'text' => '<p>Kept</p>',
            'cookie_name' => '',
        ]);

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame(
            ['banner' => '2', 'text' => '<p>Kept</p>', ...Schema::CONSENT_MODE_DEFAULTS],
            \get_option(Schema::OPTION)
        );
        $this->assertTrue(\rest_validate_value_from_schema(
            \get_option(Schema::OPTION),
            (new Schema())->toJsonSchema()
        ));
    }

    /**
     * Version 6: the Google tags get the Consent Mode signals, a saved choice is kept.
     */
    public function testTheConsentModeIsTurnedOn(): void
    {
        \update_option(Schema::VERSION_OPTION, 5);
        \add_option(Schema::OPTION, ['active' => '1']);

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame(['active' => '1', 'consent_mode' => '1'], \get_option(Schema::OPTION));
    }

    public function testTheImportedSettingsAreUpgradedFromTheirVersion(): void
    {
        $sut = new Migration(new Schema(), new LegacySnippets());

        $this->assertSame(
            [
                'active' => '1',
                ...Schema::CONSENT_LOG_DEFAULTS,
                'allowed_patterns' => ['a.example.com'],
                ...Schema::CONSENT_MODE_DEFAULTS,
            ],
            $sut->upgrade(['active' => '1', 'scroll' => '1', 'allow_script' => ['a.example.com']], 1)
        );
        $this->assertSame(['active' => '1'], $sut->upgrade(['active' => '1'], Schema::VERSION));
    }

    public function testAMissingOptionAtVersion1IsNotCreated(): void
    {
        \update_option(Schema::VERSION_OPTION, 1);

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertNull(\get_option(Schema::OPTION, null));
        $this->assertSame(Schema::VERSION, \get_option(Schema::VERSION_OPTION));
    }

    public function testNothingRunsWhenTheVersionIsCurrent(): void
    {
        \update_option(Schema::VERSION_OPTION, Schema::VERSION);

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertNull(\get_option(Schema::OPTION, null));
    }

    public function testAStoredVersionAsStringIsRead(): void
    {
        \update_option(Schema::VERSION_OPTION, (string) Schema::VERSION);

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertNull(\get_option(Schema::OPTION, null));
    }

    public function testAnInvalidStoredVersionRunsTheMigration(): void
    {
        \update_option(Schema::VERSION_OPTION, 'invalid');

        (new Migration(new Schema(), new LegacySnippets()))->migrate();

        $this->assertSame((new Schema())->defaults(), \get_option(Schema::OPTION, null));
    }

    /**
     * @param list<string> $patterns
     * @return array{id: string, name: string, category: string, patterns: list<string>}
     */
    private function customVendor(array $patterns): array
    {
        return ['id' => 'custom', 'name' => 'Custom', 'category' => 'marketing', 'patterns' => $patterns];
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
