<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\SureCookie;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Blocking\Domain\Vendors;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Cookies\Application\CookieSetting;
use ItalyCookieChoices\Migration\Application\Conversions;
use ItalyCookieChoices\Migration\Application\CookieLifetime;
use ItalyCookieChoices\Migration\Application\KnownCookies;

/**
 * The categories, the services, the cookies and the blocking rules of SureCookie.
 *
 * SureCookie blocks the services of its catalog whether they are added or not: a service the site owner added,
 * a cookie or a rule of a common vendor turns the vendor on. The other cookies become site cookies, the custom
 * ones first, and the other rules custom vendors. The added services that are not common vendors are listed.
 *
 * @phpstan-import-type Service from SureCookieStorage
 * @phpstan-type SiteCookie array{name: string, category: string, duration: string, purpose: string}
 */
final readonly class SureCookieServices
{
    /** The default categories of SureCookie, the custom ones and the uncategorized are marketing. */
    private const CATEGORIES = [
        'essential' => Category::NECESSARY,
        'functional' => Category::PREFERENCES,
        'analytics' => Category::STATISTICS,
        'marketing' => Category::MARKETING,
    ];

    /** The services of the catalog of SureCookie whose slug is not the id of the common vendor. */
    private const SERVICES = [
        'universal-analytics' => VendorId::GOOGLE_ANALYTICS,
        'facebook-pixel' => VendorId::FACEBOOK,
        'facebook-embed' => VendorId::FACEBOOK,
        'tiktok-embed' => VendorId::TIKTOK,
        'twitter' => VendorId::X,
        'clarity' => VendorId::MICROSOFT_CLARITY,
        'recaptcha' => VendorId::GOOGLE_RECAPTCHA,
        'reddit-ads' => VendorId::REDDIT,
    ];

    /** A scanned cookie that lives for the session. */
    private const SESSION = 'session';

    public function __construct(
        private SureCookieStorage $storage,
        private Conversions $conversions,
        private CookieLifetime $lifetime,
        private VendorPresets $presets,
        private VendorsFactory $vendors,
        private KnownCookies $knownCookies,
    ) {
    }

    /**
     * @param array<array-key, mixed> $settings
     * @return array{settings: array<string, mixed>, vendorsInUse: list<string>, unknown: list<string>}
     */
    public function read(array $settings): array
    {
        $services = $this->services();
        $rules = $this->rules($settings['custom_blocked_scripts'] ?? []);
        $cookies = $this->cookies($settings['custom_cookies'] ?? [], $services['cookies']);

        return [
            'settings' => [
                ConsentSetting::CATEGORIES => $this->texts($settings['cookie_categories'] ?? []),
                CookieSetting::SITE_COOKIES => $cookies['site'],
                BlockingSetting::CUSTOM_VENDORS => $rules['vendors'],
            ],
            'vendorsInUse' => \array_values(\array_unique([
                ...$services['inUse'],
                ...$rules['inUse'],
                ...$cookies['inUse'],
            ])),
            'unknown' => $services['unknown'],
        ];
    }

    /**
     * The texts of the default categories.
     *
     * @return array<string, array{label: string, description: string}>
     */
    private function texts(mixed $categories): array
    {
        $texts = [];
        foreach (\is_array($categories) ? $categories : [] as $id => $category) {
            $mapped = self::CATEGORIES[(string) $id] ?? null;
            if ($mapped === null || !\is_array($category)) {
                continue;
            }

            $texts[$mapped] = [
                'label' => $this->conversions->text($category['name'] ?? ''),
                'description' => \wp_strip_all_tags($this->conversions->text($category['description'] ?? '')),
            ];
        }

        return $texts;
    }

    /**
     * The added services: the ones of a common vendor turn it on, their cookies are not site cookies.
     *
     * @return array{inUse: list<string>, cookies: list<string>, unknown: list<string>}
     */
    private function services(): array
    {
        $result = ['inUse' => [], 'cookies' => [], 'unknown' => []];
        foreach ($this->storage->services() as $service) {
            $vendor = $this->vendorOf($service);
            if ($vendor === null) {
                $result['unknown'][] = $service['label'];
                continue;
            }

            $result['inUse'][] = $vendor;
            $result['cookies'] = [...$result['cookies'], ...$service['cookies']];
        }

        return $result;
    }

    /**
     * @param Service $service
     */
    private function vendorOf(array $service): ?string
    {
        $id = self::SERVICES[$service['slug']] ?? $service['slug'];
        foreach ($this->presets->all() as $vendor) {
            $isSame = $vendor->id() === $id
                || \strcasecmp($this->presets->name($vendor->id()), $service['label']) === 0;
            if ($isSame) {
                return $vendor->id();
            }
        }

        return null;
    }

    /**
     * The blocking rules: the ones of a common vendor turn it on, the others are custom vendors.
     *
     * @return array{
     *     inUse: list<string>,
     *     vendors: list<array{id: string, name: string, category: string, patterns: list<string>}>
     * }
     */
    private function rules(mixed $rules): array
    {
        $known = $this->vendors->known();
        $result = ['inUse' => [], 'vendors' => []];
        foreach (\is_array($rules) ? $rules : [] as $rule) {
            $pattern = \is_array($rule) ? $this->conversions->text($rule['value'] ?? '') : '';
            if ($pattern === '') {
                continue;
            }

            $vendor = $this->knownVendor($known, $pattern);
            if ($vendor !== null) {
                $result['inUse'][] = $vendor;
                continue;
            }

            $result['vendors'][] = [
                'id' => $this->conversions->id($pattern),
                'name' => $pattern,
                'category' => $this->category($rule['category'] ?? ''),
                'patterns' => [$pattern],
            ];
        }

        return $result;
    }

    private function knownVendor(Vendors $known, string $pattern): ?string
    {
        return $known->loading('https://' . \ltrim($pattern, '/'))?->id();
    }

    /**
     * The custom cookies, then the scanned ones with another name: the cookies of a common vendor turn it on.
     *
     * @param list<string> $ofServices The ids of the custom cookies of the services of a common vendor.
     * @return array{site: list<SiteCookie>, inUse: list<string>}
     */
    private function cookies(mixed $custom, array $ofServices): array
    {
        $result = ['site' => [], 'inUse' => []];
        $names = [];
        foreach (\is_array($custom) ? $custom : [] as $id => $cookie) {
            if (!\is_array($cookie) || \in_array((string) $id, $ofServices, true)) {
                continue;
            }

            $result = $this->withCookie($result, $cookie, $this->conversions->text($cookie['category'] ?? ''));
            $names[] = $this->conversions->text($cookie['name'] ?? '');
        }

        foreach ($this->storage->scannedCookies() as $category => $rows) {
            foreach (\is_array($rows) ? $rows : [] as $cookie) {
                $isNew = \is_array($cookie)
                    && !\in_array($this->conversions->text($cookie['name'] ?? ''), $names, true);
                $result = $isNew ? $this->withCookie($result, $cookie, (string) $category) : $result;
            }
        }

        return $result;
    }

    /**
     * @param array{site: list<SiteCookie>, inUse: list<string>} $result
     * @param array<array-key, mixed> $cookie
     * @return array{site: list<SiteCookie>, inUse: list<string>}
     */
    private function withCookie(array $result, array $cookie, string $category): array
    {
        $name = $this->conversions->text($cookie['name'] ?? '');
        if ($name === '') {
            return $result;
        }

        $vendor = $this->knownCookies->vendorOf($name);
        if ($vendor !== null) {
            $result['inUse'][] = $vendor;

            return $result;
        }

        $description = $this->conversions->text($cookie['description'] ?? '');
        $result['site'][] = [
            'name' => $name,
            'category' => $this->category($category),
            'duration' => $this->duration($cookie),
            'purpose' => \wp_strip_all_tags(
                $description !== '' ? $description : $this->conversions->text($cookie['purpose'] ?? '')
            ),
        ];

        return $result;
    }

    private function category(mixed $id): string
    {
        return self::CATEGORIES[$this->conversions->text($id)] ?? Category::MARKETING;
    }

    /**
     * The lifetime of a cookie: SureCookie keeps whole days, 0 for the session, or a text.
     *
     * @param array<array-key, mixed> $cookie
     */
    private function duration(array $cookie): string
    {
        $duration = $this->conversions->text($cookie['duration'] ?? '');
        $isSession = ($cookie['expiry_bucket'] ?? '') === self::SESSION
            || (\is_numeric($duration) && (float) $duration <= 0);
        if ($isSession) {
            return $this->lifetime->text(null);
        }

        return \is_numeric($duration) ? $this->lifetime->text((int) $duration * CookieLifetime::DAY) : $duration;
    }
}
