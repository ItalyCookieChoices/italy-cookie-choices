<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\UI;

use ItalyCookieChoices\Cookies\Application\BuildingCookieTable;
use ItalyCookieChoices\Cookies\Domain\Cookie;
use ItalyCookieChoices\Integrations\Application\WpConsentApiMapping;
use ItalyCookieChoices\Integrations\UI\WpConsentApiCookies;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class WpConsentApiCookiesTest extends WPTestCase
{
    public function testItListensToTheCookieTable(): void
    {
        $this->assertSame(
            [BuildingCookieTable::class => 'addCookies'],
            \iterator_to_array($this->makeSut([])->getSubscribedEvents())
        );
    }

    public function testItAddsTheCookiesRegisteredByThePlugins(): void
    {
        $event = new BuildingCookieTable([]);

        $this->makeSut([
            'AMP_token' => $this->info('AMP', 'marketing', 'Session', 'Stores a unique user id.'),
            'wp_stats' => $this->info('Stats', 'statistics-anonymous', '1 year', 'Counts the visits.'),
            'wp_session' => $this->info('Shop', 'functional', 'Session', 'Keeps the cart.'),
        ])->addCookies($event);

        $this->assertEquals(
            [
                new Cookie('AMP_token', 'AMP', 'marketing', 'Stores a unique user id.', 'Session'),
                new Cookie('wp_stats', 'Stats', 'statistics', 'Counts the visits.', '1 year'),
                new Cookie('wp_session', 'Shop', 'necessary', 'Keeps the cart.', 'Session'),
            ],
            $event->cookies()
        );
    }

    public function testTheInvalidEntriesAreSkipped(): void
    {
        $event = new BuildingCookieTable([]);

        $this->makeSut([
            'unknown_category' => $this->info('A', 'social', 'Session', 'Unknown.'),
            'not_an_entry' => 'text',
            '' => $this->info('B', 'marketing', 'Session', 'No name.'),
        ])->addCookies($event);

        $this->assertSame([], $event->cookies());
    }

    /**
     * @return array<string, string>
     */
    private function info(string $service, string $category, string $expires, string $function): array
    {
        return [
            'plugin_or_service' => $service,
            'category' => $category,
            'expires' => $expires,
            'function' => $function,
        ];
    }

    /**
     * @param array<array-key, mixed> $cookieInfo
     */
    private function makeSut(array $cookieInfo): WpConsentApiCookies
    {
        return new WpConsentApiCookies(
            new WpConsentApiMapping(new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState())),
            static fn(): array => $cookieInfo
        );
    }
}
