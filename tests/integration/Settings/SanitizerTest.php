<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\Sanitizer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SanitizerTest extends WPTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_settings_errors'] = [];
    }

    public function testEmptyInputGetsTheDefaults(): void
    {
        $this->assertSame(
            [
                'banner_bg' => '#fff',
                'banner_text_color' => '#000',
                'bannerStyle' => 'bannerStyle',
                'contentStyle' => 'contentStyle',
                'consentText' => 'consentText',
                'infoClass' => 'italybtn',
                'closeClass' => 'italybtn',
                'cookie_name' => 'displayCookieConsent',
                'cookie_value' => 'y',
            ],
            (new Sanitizer())([])
        );
    }

    public function testEmptyCookieNameAndValueRaiseSettingsErrors(): void
    {
        (new Sanitizer())(['cookie_name' => '', 'cookie_value' => '']);

        $this->assertSame(
            [
                [
                    'setting' => 'italy_cookie_id',
                    'code' => 'cookie_name_ID',
                    'message' => 'Cookie name field it can\'t be empty. Restored default name.',
                    'type' => 'error',
                ],
                [
                    'setting' => 'italy_cookie_id',
                    'code' => 'cookie_name_ID',
                    'message' => 'Cookie value field it can\'t be empty. Restored default value.',
                    'type' => 'error',
                ],
            ],
            \get_settings_errors('italy_cookie_id')
        );
    }

    public function testValidCookieNameAndValueRaiseNoErrors(): void
    {
        (new Sanitizer())(['cookie_name' => 'name', 'cookie_value' => 'value']);

        $this->assertSame([], \get_settings_errors('italy_cookie_id'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function passThroughFields(): iterable
    {
        $fields = [
            'active',
            'banner',
            'reload',
            'html_margin',
            'js_template',
            'target',
            'nofollow',
            'all_block',
            'consent_log',
            'consent_log_retention',
            'consent_mode',
            'consent_mode_advanced',
            'youtube_nocookie',
            'uet_consent_mode',
            'clarity_consent_mode',
            'floating_button',
            'floating_button_position',
            'global_privacy_control',
        ];

        foreach ($fields as $field) {
            yield $field => [$field];
        }
    }

    /**
     * @dataProvider passThroughFields
     */
    public function testFieldsArePassedThroughUntouched(string $field): void
    {
        $value = "<script>raw</script>\n";

        $result = (new Sanitizer())([$field => $value]);

        $this->assertSame($value, $result[$field]);
    }

    public function testTheCategoryTextsAreSanitizedWithoutTheEmptyOnes(): void
    {
        $result = (new Sanitizer())([
            'categories' => [
                'statistics' => ['label' => '<b>Analytics</b>', 'description' => "Anonymous\nvisits.", 'other' => 'x'],
                'marketing' => ['label' => '', 'description' => ''],
                'Not Valid!' => ['label' => 'Kept with a valid id'],
                'preferences' => 'not texts',
            ],
        ]);

        $this->assertSame(
            [
                'statistics' => ['label' => 'Analytics', 'description' => "Anonymous\nvisits."],
                'notvalid' => ['label' => 'Kept with a valid id'],
            ],
            $result['categories']
        );
    }

    public function testTheDisabledVendorsAreUniqueIds(): void
    {
        $result = (new Sanitizer())(['disabled_vendors' => ['youtube', 'YouTube', '', ['x'], 'google-maps']]);

        $this->assertSame(['youtube', 'google-maps'], $result['disabled_vendors']);
    }

    public function testTheCustomVendorsNeedACategoryAndAPattern(): void
    {
        $result = (new Sanitizer())([
            'custom_vendors' => [
                ['id' => 'tracker', 'name' => '<b>Tracker</b>', 'category' => 'statistics', 'patterns' => [
                    ' tracker.example.com/t.js?a=%20b ',
                    "fbq(\n'init'",
                    '',
                    'tracker.example.com/t.js?a=%20b',
                ]],
                ['id' => 'no-patterns', 'name' => 'None', 'category' => 'marketing', 'patterns' => []],
                ['id' => 'no-category', 'name' => 'None', 'patterns' => ['a.example.com']],
                'not a vendor',
            ],
        ]);

        $this->assertSame(
            [
                [
                    'id' => 'tracker',
                    'name' => 'Tracker',
                    'category' => 'statistics',
                    'patterns' => ['tracker.example.com/t.js?a=%20b', "fbq( 'init'"],
                    'cookies' => [],
                ],
            ],
            $result['custom_vendors']
        );
    }

    public function testTheCookiesOfTheCustomVendorsNeedAName(): void
    {
        $result = (new Sanitizer())([
            'custom_vendors' => [
                [
                    'id' => 'tracker',
                    'name' => 'Tracker',
                    'category' => 'statistics',
                    'patterns' => ['t.js'],
                    'cookies' => [
                        ['name' => ' <b>_trk</b> ', 'duration' => ' 1 year ', 'purpose' => "Counts\nthe visits"],
                        ['name' => '', 'duration' => 'Session', 'purpose' => 'No name'],
                        'not a cookie',
                    ],
                ],
            ],
        ]);

        $this->assertIsArray($result['custom_vendors']);
        $vendor = $result['custom_vendors'][0] ?? null;
        $this->assertIsArray($vendor);
        $this->assertSame(
            [['name' => '_trk', 'duration' => '1 year', 'purpose' => 'Counts the visits']],
            $vendor['cookies'] ?? null
        );
    }

    public function testTheSiteCookiesNeedANameAndACategory(): void
    {
        $result = (new Sanitizer())([
            'site_cookies' => [
                [
                    'name' => 'wordpress_logged_in_*',
                    'category' => 'necessary',
                    'duration' => '14 days',
                    'purpose' => 'Login',
                ],
                ['name' => 'lang', 'category' => 'Not Valid!', 'duration' => '1 year', 'purpose' => 'Language'],
                ['name' => '', 'category' => 'necessary', 'duration' => '', 'purpose' => ''],
                ['name' => 'cart', 'duration' => '2 days', 'purpose' => 'Cart'],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'name' => 'wordpress_logged_in_*',
                    'category' => 'necessary',
                    'duration' => '14 days',
                    'purpose' => 'Login',
                ],
                ['name' => 'lang', 'category' => 'notvalid', 'duration' => '1 year', 'purpose' => 'Language'],
            ],
            $result['site_cookies']
        );
    }

    public function testTheCustomVendorsGetAUniqueIdFromTheirName(): void
    {
        $vendor = ['category' => 'marketing', 'patterns' => ['a.example.com']];

        $result = (new Sanitizer())([
            'custom_vendors' => [
                ['id' => '', 'name' => 'My Ads', ...$vendor],
                ['id' => 'myads', 'name' => 'Other', ...$vendor],
                ['id' => '', 'name' => '', ...$vendor],
            ],
        ]);

        $this->assertIsArray($result['custom_vendors']);
        $this->assertSame(['myads', 'myads-2', 'custom'], \array_column($result['custom_vendors'], 'id'));
    }

    /**
     * @return iterable<string, array{string, mixed, string}>
     */
    public function tagIds(): iterable
    {
        yield 'measurement id' => ['ga_measurement_id', ' g-abc123 ', 'G-ABC123'];
        yield 'universal analytics id' => ['ga_measurement_id', 'UA-123-1', ''];
        yield 'container id' => ['gtm_container_id', 'gtm-abc1', 'GTM-ABC1'];
        yield 'container id with a script' => ['gtm_container_id', 'GTM-A");alert(1);//', ''];
        yield 'pixel id' => ['facebook_pixel_id', '123456789', '123456789'];
        yield 'pixel id with letters' => ['facebook_pixel_id', '123abc', ''];
        yield 'pixel id not a string' => ['facebook_pixel_id', ['1'], ''];
    }

    /**
     * @dataProvider tagIds
     */
    public function testTheTagIdsHaveTheShapeOfTheirTag(string $field, mixed $value, string $expected): void
    {
        $this->assertSame($expected, (new Sanitizer())([$field => $value])[$field]);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public function gatewayPaths(): iterable
    {
        yield 'path' => ['/metrics', '/metrics'];
        yield 'nested path' => ['/metrics/v1', '/metrics/v1'];
        yield 'trailing slash' => [' /metrics/ ', '/metrics'];
        yield 'without the slash' => ['metrics', '/metrics'];
        yield 'address' => ['https://example.com/metrics/?id=G-1', '/metrics'];
        yield 'root' => ['/', ''];
        yield 'empty' => ['', ''];
        yield 'space' => ['/my metrics', ''];
        yield 'markup' => ['/m"><script>', ''];
        yield 'not a string' => [['/metrics'], ''];
    }

    /**
     * @dataProvider gatewayPaths
     */
    public function testTheGatewayPathIsAPathOfTheSite(mixed $value, string $expected): void
    {
        $sanitized = (new Sanitizer())(['google_tag_gateway_path' => $value]);

        $this->assertSame($expected, $sanitized['google_tag_gateway_path']);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public function cookieDomains(): iterable
    {
        yield 'the parent domain of the site' => ['.Example.com', 'example.com'];
        yield 'the host of the site' => ['www.example.com', 'www.example.com'];
        yield 'empty' => ['', ''];
        yield 'another domain' => ['example.org', ''];
        yield 'not a string' => [['example.com'], ''];
    }

    /**
     * @dataProvider cookieDomains
     */
    public function testTheCookieDomainIsTheHostOfTheSiteOrOneOfItsParents(mixed $value, string $expected): void
    {
        \add_filter('home_url', static fn(): string => 'https://www.example.com');

        $sanitized = (new Sanitizer())(['cookie_domain' => $value]);

        $this->assertSame($expected, $sanitized['cookie_domain']);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public function logos(): iterable
    {
        yield 'attachment id' => ['42', '42'];
        yield 'number' => [42, '42'];
        yield 'empty' => ['', ''];
        yield 'zero' => ['0', ''];
        yield 'text' => ['logo.png', ''];
        yield 'negative' => ['-3', ''];
    }

    /**
     * @dataProvider logos
     */
    public function testTheLogoIsAnAttachmentId(mixed $value, string $expected): void
    {
        $this->assertSame($expected, (new Sanitizer())(['banner_logo' => $value])['banner_logo']);
    }

    public function testTheAllowedPatternsAreUniqueTexts(): void
    {
        $result = (new Sanitizer())(['allowed_patterns' => ['iccAllowed', ' iccAllowed ', '', 42, ['x']]]);

        $this->assertSame(['iccAllowed', '42'], $result['allowed_patterns']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function textFields(): iterable
    {
        $fields = [
            'url',
            'slug',
            'anchor_text',
            'button_text',
            'banner_bg',
            'banner_text_color',
            'customCSS',
            'bannerStyle',
            'contentStyle',
            'consentText',
            'infoClass',
            'closeClass',
            'cookie_name',
            'cookie_value',
            'content_message_button_text',
            'excluded_pages',
            'floating_button_label',
        ];

        foreach ($fields as $field) {
            yield $field => [$field];
        }
    }

    /**
     * @dataProvider textFields
     */
    public function testTextFieldsAreSanitized(string $field): void
    {
        $result = (new Sanitizer())([$field => "  <b>bold</b>\n  text  "]);

        $this->assertSame('bold text', $result[$field]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function htmlFields(): iterable
    {
        yield 'text' => ['text'];
        yield 'content_message_text' => ['content_message_text'];
    }

    /**
     * @dataProvider htmlFields
     */
    public function testHtmlFieldsKeepThePostHtml(string $field): void
    {
        $result = (new Sanitizer())([
            $field => '<a href="https://example.com">policy</a><script>alert(1)</script>',
        ]);

        $this->assertSame('<a href="https://example.com">policy</a>alert(1)', $result[$field]);
    }

    /**
     * Changed in #235: accepting on scroll and on the second page view is not a valid consent, they are removed.
     * Changed in #236: the vendors are always blocked, the 2.x textareas moved to the lists.
     *
     * @return iterable<string, array{string}>
     */
    public function removedFields(): iterable
    {
        yield 'scroll' => ['scroll'];
        yield 'secondView' => ['secondView'];
        yield 'block' => ['block'];
        yield 'widget_block' => ['widget_block'];
        yield 'custom_script_block' => ['custom_script_block'];
        yield 'custom_script_block_body_exclude' => ['custom_script_block_body_exclude'];
    }

    /**
     * @dataProvider removedFields
     */
    public function testRemovedFieldsAreNotSaved(string $field): void
    {
        $result = (new Sanitizer())([$field => '1', 'active' => '1']);

        $this->assertArrayNotHasKey($field, $result);
        $this->assertSame('1', $result['active']);
    }

    public function testMissingOptionalFieldsAreNotAdded(): void
    {
        $result = (new Sanitizer())([]);

        $fields = [
            'active',
            'text',
            'url',
            'slug',
            'customCSS',
            'all_block',
            'categories',
            'disabled_vendors',
            'custom_vendors',
            'allowed_patterns',
            'content_message_text',
            'nofollow',
            'excluded_pages',
        ];

        foreach ($fields as $field) {
            $this->assertArrayNotHasKey($field, $result);
        }
    }

    public function testSavingTheOptionRunsTheSanitizer(): void
    {
        \update_option('italy_cookie_choices', ['active' => '1', 'anchor_text' => '<b>More</b>']);

        $saved = \get_option('italy_cookie_choices');
        $this->assertIsArray($saved);
        $this->assertSame('1', $saved['active']);
        $this->assertSame('More', $saved['anchor_text']);
        $this->assertSame('displayCookieConsent', $saved['cookie_name']);
    }
}
