<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Cookies\Application;

use ItalyCookieChoices\Cookies\Application\BuildingCookieTable;
use ItalyCookieChoices\Cookies\Application\CookieTableFactory;
use ItalyCookieChoices\Cookies\Domain\Cookie;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieTableFactoryTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        \update_option('blogname', 'My site');
    }

    public function testTheConsentCookieIsNecessary(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $cookie = $this->cookie('icc_consent');

        $this->assertNotNull($cookie);
        $this->assertSame('necessary', $cookie->category());
        $this->assertSame('My site', $cookie->provider());
        $this->assertSame('182 days', $cookie->duration());
        $this->assertNotSame('', $cookie->purpose());
    }

    public function testThePresetsListTheirCookies(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $cookie = $this->cookie('_ga');

        $this->assertNotNull($cookie);
        $this->assertSame('statistics', $cookie->category());
        $this->assertSame('Google Analytics', $cookie->provider());
        $this->assertSame('2 years', $cookie->duration());
    }

    public function testThePresetsTurnedOffAreNotListed(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'disabled_vendors' => ['google-analytics']]);

        $this->assertNull($this->cookie('_ga'));
    }

    public function testTheCustomVendorsListTheirCookies(): void
    {
        \update_option(Schema::OPTION, [
            'active' => '1',
            'custom_vendors' => [[
                'id' => 'tracker',
                'name' => 'Tracker',
                'category' => 'statistics',
                'patterns' => ['tracker.example.com'],
                'cookies' => [['name' => '_trk', 'duration' => '1 year', 'purpose' => 'Counts the visits']],
            ]],
        ]);

        $cookie = $this->cookie('_trk');

        $this->assertNotNull($cookie);
        $this->assertSame('Tracker', $cookie->provider());
        $this->assertSame('statistics', $cookie->category());
        $this->assertSame('1 year', $cookie->duration());
        $this->assertSame('Counts the visits', $cookie->purpose());
    }

    public function testTheSiteCookiesAreListed(): void
    {
        \update_option(Schema::OPTION, [
            'active' => '1',
            'site_cookies' => [
                ['name' => 'wp_lang', 'category' => 'preferences', 'duration' => 'Session', 'purpose' => 'Language'],
            ],
        ]);

        $cookie = $this->cookie('wp_lang');

        $this->assertNotNull($cookie);
        $this->assertSame('My site', $cookie->provider());
        $this->assertSame('preferences', $cookie->category());
    }

    public function testTheListenersChangeTheCookies(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);
        \add_action(BuildingCookieTable::class, static function (BuildingCookieTable $event): void {
            $event->addCookie(new Cookie('theme_dark', 'Theme', 'preferences', 'Dark mode', '1 year'));
            $event->removeCookie('_ga');
        });

        $this->assertSame('Theme', $this->cookie('theme_dark')?->provider());
        $this->assertNull($this->cookie('_ga'));
    }

    private function cookie(string $name): ?Cookie
    {
        $table = $this->service(CookieTableFactory::class)->create();
        foreach (['necessary', 'preferences', 'statistics', 'marketing'] as $category) {
            foreach ($table->inCategory($category) as $cookie) {
                if ($cookie->name() === $name) {
                    return $cookie;
                }
            }
        }

        return null;
    }
}
