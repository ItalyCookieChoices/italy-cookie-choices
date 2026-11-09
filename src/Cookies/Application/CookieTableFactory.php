<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cookies\Application;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\Infrastructure\CookieConsentRepository;
use ItalyCookieChoices\Cookies\Domain\Cookie;
use ItalyCookieChoices\Cookies\Domain\CookieTable;
use ItalyCookieChoices\Settings\Options;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Collects the cookies of the site: the consent cookie of the plugin, the cookies of the vendors blocked
 * until the consent, the cookies of the site written by the site owner and the listeners of BuildingCookieTable.
 */
final readonly class CookieTableFactory
{
    public function __construct(
        private VendorsFactory $vendors,
        private VendorPresets $presets,
        private PresetCookies $presetCookies,
        private ConsentPolicyFactory $policy,
        private CookieDurations $durations,
        private Options $options,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function create(): CookieTable
    {
        $event = new BuildingCookieTable([
            $this->consentCookie(),
            ...$this->vendorCookies(),
            ...$this->siteCookies(),
        ]);
        $this->dispatcher->dispatch($event);

        return new CookieTable($event->cookies());
    }

    private function consentCookie(): Cookie
    {
        return new Cookie(
            CookieConsentRepository::COOKIE_NAME,
            $this->siteName(),
            Category::NECESSARY,
            \__('Stores the consent of the visitor to the cookie categories.', 'italy-cookie-choices'),
            $this->durations->days($this->policy->create()->duration()->days())
        );
    }

    /**
     * @return list<Cookie>
     */
    private function vendorCookies(): array
    {
        $customVendors = \array_column($this->options->records('custom_vendors'), null, 'id');

        $cookies = [];
        foreach ($this->vendors->create() as $vendor) {
            $custom = $customVendors[$vendor->id()] ?? null;
            $cookies = [
                ...$cookies,
                ...(\is_array($custom) ? $this->customVendorCookies($vendor, $custom) : $this->presetCookies($vendor)),
            ];
        }

        return \array_values(\array_filter($cookies));
    }

    /**
     * @return list<Cookie|null>
     */
    private function presetCookies(Vendor $vendor): array
    {
        return \array_map(
            fn(array $record): ?Cookie => $this->vendorCookie($vendor, $this->presets->name($vendor->id()), $record),
            $this->presetCookies->of($vendor->id())
        );
    }

    /**
     * @param array<array-key, mixed> $custom The saved custom vendor.
     * @return list<Cookie|null>
     */
    private function customVendorCookies(Vendor $vendor, array $custom): array
    {
        $provider = $this->text($custom['name'] ?? '');

        return \array_map(
            fn(array $record): ?Cookie => $this->vendorCookie($vendor, $provider, $record),
            $this->records($custom['cookies'] ?? [])
        );
    }

    /**
     * @param array{name: string, duration: string, purpose: string} $record
     */
    private function vendorCookie(Vendor $vendor, string $provider, array $record): ?Cookie
    {
        try {
            return new Cookie(
                $record['name'],
                $provider !== '' ? $provider : $vendor->id(),
                $vendor->category(),
                $record['purpose'],
                $record['duration']
            );
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return list<Cookie>
     */
    private function siteCookies(): array
    {
        $cookies = [];
        foreach ($this->options->records('site_cookies') as $record) {
            $cookie = $this->siteCookie($record);
            if ($cookie === null) {
                continue;
            }

            $cookies[] = $cookie;
        }

        return $cookies;
    }

    /**
     * @param array<array-key, mixed> $record
     */
    private function siteCookie(array $record): ?Cookie
    {
        try {
            return new Cookie(
                $this->text($record['name'] ?? ''),
                $this->siteName(),
                $this->text($record['category'] ?? ''),
                $this->text($record['purpose'] ?? ''),
                $this->text($record['duration'] ?? '')
            );
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return list<array{name: string, duration: string, purpose: string}>
     */
    private function records(mixed $value): array
    {
        $records = [];
        foreach (\is_array($value) ? $value : [] as $record) {
            if (!\is_array($record)) {
                continue;
            }

            $records[] = [
                'name' => $this->text($record['name'] ?? ''),
                'duration' => $this->text($record['duration'] ?? ''),
                'purpose' => $this->text($record['purpose'] ?? ''),
            ];
        }

        return $records;
    }

    private function siteName(): string
    {
        $name = \get_bloginfo('name');

        return $name !== '' ? $name : (string) \wp_parse_url(\home_url(), \PHP_URL_HOST);
    }

    private function text(mixed $value): string
    {
        return \is_string($value) ? $value : '';
    }
}
