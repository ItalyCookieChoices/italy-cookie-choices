<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\UI;

use ItalyCookieChoices\Cookies\Application\BuildingCookieTable;
use ItalyCookieChoices\Cookies\Domain\Cookie;
use ItalyCookieChoices\Integrations\Application\WpConsentApiMapping;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Adds to the cookie table the cookies the other plugins register with wp_add_cookie_info() of the WP Consent API.
 *
 * Each category of the API goes back to the first category of the plugin that allows it.
 */
final readonly class WpConsentApiCookies implements SubscriberInterface
{
    /**
     * @var \Closure(): array<array-key, mixed>
     */
    private \Closure $cookieInfo;

    /**
     * @param (\Closure(): array<array-key, mixed>)|null $cookieInfo wp_get_cookie_info() by default.
     */
    public function __construct(
        private WpConsentApiMapping $mapping,
        ?\Closure $cookieInfo = null,
    ) {
        $this->cookieInfo = $cookieInfo ?? $this->registeredCookies(...);
    }

    public function getSubscribedEvents(): iterable
    {
        yield BuildingCookieTable::class => 'addCookies';
    }

    public function addCookies(BuildingCookieTable $event): void
    {
        foreach (($this->cookieInfo)() as $name => $info) {
            $cookie = $this->cookie((string) $name, $info);
            if ($cookie === null) {
                continue;
            }

            $event->addCookie($cookie);
        }
    }

    private function cookie(string $name, mixed $info): ?Cookie
    {
        if (!\is_array($info)) {
            return null;
        }

        $category = $this->mapping->categoriesAllowing($this->text($info['category'] ?? ''))[0] ?? null;
        if ($category === null) {
            return null;
        }

        try {
            return new Cookie(
                $name,
                $this->text($info['plugin_or_service'] ?? ''),
                $category,
                $this->text($info['function'] ?? ''),
                $this->text($info['expires'] ?? '')
            );
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private function registeredCookies(): array
    {
        return \function_exists('wp_get_cookie_info') ? (array) \wp_get_cookie_info() : [];
    }

    private function text(mixed $value): string
    {
        return \is_string($value) ? $value : '';
    }
}
