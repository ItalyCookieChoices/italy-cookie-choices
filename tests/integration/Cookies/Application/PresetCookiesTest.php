<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Cookies\Application;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Cookies\Application\CookieDurations;
use ItalyCookieChoices\Cookies\Application\PresetCookies;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class PresetCookiesTest extends WPTestCase
{
    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public function vendorCookies(): iterable
    {
        yield 'Google Analytics' => ['google-analytics', ['_ga', '_ga_*']];
        yield 'Google Ads' => ['google-ads', ['_gcl_au', '_gcl_aw', 'IDE', 'test_cookie']];
        yield 'YouTube' => ['youtube', ['YSC', '__Secure-YNID', 'PREF']];
        yield 'Microsoft Clarity' => ['microsoft-clarity', ['_clck', '_clsk']];
        yield 'HubSpot' => ['hubspot', ['__hstc', 'hubspotutk', '__hssc', '__hssrc']];
        yield 'Matomo Cloud' => ['matomo-cloud', ['_pk_id.*', '_pk_ses.*', '_pk_ref.*']];
        yield 'reCAPTCHA' => ['google-recaptcha', ['_GRECAPTCHA']];
    }

    /**
     * @dataProvider vendorCookies
     * @param list<string> $names
     */
    public function testAVendorListsItsCookies(string $vendor, array $names): void
    {
        $this->assertSame($names, \array_column((new PresetCookies(new CookieDurations()))->of($vendor), 'name'));
    }

    public function testEveryCookieHasADurationAndAPurpose(): void
    {
        $sut = new PresetCookies(new CookieDurations());

        foreach ((new VendorPresets())->all() as $vendor) {
            foreach ($sut->of($vendor->id()) as $cookie) {
                $this->assertNotSame('', $cookie['duration'], $cookie['name']);
                $this->assertNotSame('', $cookie['purpose'], $cookie['name']);
            }
        }
    }

    public function testTheDurationsAreWrittenOut(): void
    {
        $cookies = \array_column((new PresetCookies(new CookieDurations()))->of('hubspot'), 'duration', 'name');

        $this->assertSame('6 months', $cookies['__hstc'] ?? null);
        $this->assertSame('30 minutes', $cookies['__hssc'] ?? null);
        $this->assertSame('Session', $cookies['__hssrc'] ?? null);
    }

    public function testGoogleTagManagerAndTheUnknownVendorsHaveNoCookies(): void
    {
        $sut = new PresetCookies(new CookieDurations());

        $this->assertSame([], $sut->of('google-tag-manager'));
        $this->assertSame([], $sut->of('custom'));
    }
}
