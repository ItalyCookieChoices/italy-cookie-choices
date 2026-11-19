<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\Complianz;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Blocking\Domain\CustomScript;
use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Cookies\Application\CookieSetting;
use ItalyCookieChoices\Migration\Application\Conversions;

/**
 * The services of Complianz: the presets in use, the scripts of the script center and the cookies.
 */
final readonly class ComplianzServices
{
    /**
     * The services and social networks of Complianz with a preset in Cookie Choices.
     */
    private const PRESETS = [
        'youtube' => VendorId::YOUTUBE,
        'vimeo' => VendorId::VIMEO,
        'google-maps' => VendorId::GOOGLE_MAPS,
        'google-recaptcha' => VendorId::GOOGLE_RECAPTCHA,
        'hotjar' => VendorId::HOTJAR,
        'spotify' => VendorId::SPOTIFY,
        'soundcloud' => VendorId::SOUNDCLOUD,
        'hubspot' => VendorId::HUBSPOT,
        'microsoftads' => VendorId::MICROSOFT_ADVERTISING,
        'facebook' => VendorId::FACEBOOK,
        'twitter' => VendorId::X,
        'linkedin' => VendorId::LINKEDIN,
        'instagram' => VendorId::INSTAGRAM,
        'tiktok' => VendorId::TIKTOK,
        'pinterest' => VendorId::PINTEREST,
        'snapchat' => VendorId::SNAPCHAT,
    ];

    /**
     * The statistics tools of Complianz with a preset in Cookie Choices.
     */
    private const STATISTICS = [
        'google-analytics' => VendorId::GOOGLE_ANALYTICS,
        'gtg' => VendorId::GOOGLE_ANALYTICS,
        'google-tag-manager' => VendorId::GOOGLE_TAG_MANAGER,
        'clarity' => VendorId::MICROSOFT_CLARITY,
    ];

    /**
     * The words of the purposes of Complianz, from cookiedatabase.org, and their category.
     */
    private const PURPOSES = [
        'statistic' => Category::STATISTICS,
        'marketing' => Category::MARKETING,
        'tracking' => Category::MARKETING,
        'advertis' => Category::MARKETING,
        'preference' => Category::PREFERENCES,
    ];

    /**
     * The lists of services of Complianz, with the answer that turns them on.
     */
    private const SERVICE_LISTS = [
        'thirdparty_services_on_site' => 'uses_thirdparty_services',
        'socialmedia_on_site' => 'uses_social_media',
    ];

    public function __construct(
        private ComplianzStorage $storage,
        private Conversions $conversions,
    ) {
    }

    /**
     * The presets of the services Complianz knows the site uses.
     *
     * @return list<string>
     */
    public function vendorsInUse(): array
    {
        $vendors = [];
        foreach ($this->servicesInUse() as $service) {
            $vendor = self::STATISTICS[$service] ?? self::PRESETS[$service] ?? null;
            if ($vendor !== null) {
                $vendors[] = $vendor;
            }
        }

        return \array_values(\array_unique($vendors));
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $scripts = $this->storage->scripts();
        $cookies = $this->cookiesByService();
        $vendors = $this->customVendors($this->entries($scripts['block_script'] ?? []), $cookies['vendors']);

        return \array_filter(
            [
                BlockingSetting::CUSTOM_VENDORS => $vendors,
                BlockingSetting::CUSTOM_SCRIPTS => $this->customScripts($this->entries($scripts['add_script'] ?? [])),
                BlockingSetting::ALLOWED_PATTERNS => $this->allowedPatterns(
                    $this->entries($scripts['whitelist_script'] ?? [])
                ),
                CookieSetting::SITE_COOKIES => $cookies['site'],
            ],
            static fn(array $value): bool => $value !== []
        );
    }

    /**
     * @return list<string>
     */
    public function notes(): array
    {
        $notes = [];
        $unknown = \array_diff(
            $this->servicesInUse(),
            \array_keys(self::PRESETS),
            \array_keys(self::STATISTICS)
        );
        if ($unknown !== []) {
            $notes[] = \sprintf(
                /* translators: %s: the names of the services, separated by commas. */
                \__(
                    'These services have no preset: add them as custom vendors if they set cookies: %s.',
                    'italy-cookie-choices'
                ),
                \implode(', ', $unknown)
            );
        }

        $orphans = $this->orphanServices();
        if ($orphans !== []) {
            $notes[] = \sprintf(
                /* translators: %s: the names of the services, separated by commas. */
                \__(
                    'The cookies of these services are not imported, add them to a custom vendor: %s.',
                    'italy-cookie-choices'
                ),
                \implode(', ', $orphans)
            );
        }

        return $notes;
    }

    /**
     * The services, the social networks and the statistics tool Complianz lists, in its slugs.
     *
     * @return list<string>
     */
    private function servicesInUse(): array
    {
        $options = $this->storage->options();
        $services = [];
        $statistics = $options['compile_statistics'] ?? '';
        if (\is_string($statistics) && $statistics !== '') {
            $services[] = $statistics;
        }

        foreach (self::SERVICE_LISTS as $list => $uses) {
            if (($options[$uses] ?? 'yes') !== 'yes' || !\is_array($options[$list] ?? null)) {
                continue;
            }

            $services = [...$services, ...\array_filter($options[$list], \is_string(...))];
        }

        return \array_values(\array_unique($services));
    }

    /**
     * The blocked scripts turned on, as custom vendors, with the cookies of the service of the same name.
     *
     * @param list<array<array-key, mixed>> $entries
     * @param array<string, list<array{name: string, duration: string, purpose: string}>> $cookies
     * @return list<array<string, mixed>>
     */
    private function customVendors(array $entries, array $cookies): array
    {
        $vendors = [];
        foreach ($entries as $entry) {
            $name = $this->text($entry['name'] ?? '');
            $patterns = $this->texts($entry['urls'] ?? []);
            if ($name === '' || $patterns === []) {
                continue;
            }

            $vendor = [
                'id' => $this->conversions->id($name),
                'name' => $name,
                'category' => $this->category($entry['category'] ?? ''),
                'patterns' => $patterns,
            ];
            $vendorCookies = $cookies[\strtolower($name)] ?? [];
            $vendors[] = $vendorCookies === [] ? $vendor : [...$vendor, 'cookies' => $vendorCookies];
        }

        return $vendors;
    }

    /**
     * The scripts run after the consent, at the end of the body like Complianz does.
     *
     * @param list<array<array-key, mixed>> $entries
     * @return list<array{name: string, category: string, position: string, code: string}>
     */
    private function customScripts(array $entries): array
    {
        $scripts = [];
        foreach ($entries as $entry) {
            $code = \trim($this->text($entry['editor'] ?? ''));
            if ($code === '') {
                continue;
            }

            $scripts[] = [
                'name' => $this->text($entry['name'] ?? ''),
                'category' => $this->category($entry['category'] ?? ''),
                'position' => CustomScript::BODY,
                'code' => "<script>\n" . $code . "\n</script>",
            ];
        }

        return $scripts;
    }

    /**
     * @param list<array<array-key, mixed>> $entries
     * @return list<string>
     */
    private function allowedPatterns(array $entries): array
    {
        $patterns = [];
        foreach ($entries as $entry) {
            $patterns = [...$patterns, ...$this->texts($entry['urls'] ?? [])];
        }

        return \array_values(\array_unique($patterns));
    }

    /**
     * The cookies of the site, without a service or on its own domain, and the ones of each service by name.
     *
     * @return array{
     *     site: list<array{name: string, category: string, duration: string, purpose: string}>,
     *     vendors: array<string, list<array{name: string, duration: string, purpose: string}>>
     * }
     */
    private function cookiesByService(): array
    {
        $services = $this->storage->serviceNames();
        $site = [];
        $vendors = [];
        foreach ($this->storage->cookies() as $row) {
            $cookie = [
                'name' => $this->text($row['name'] ?? ''),
                'duration' => $this->text($row['retention'] ?? ''),
                'purpose' => $this->text($row['cookieFunction'] ?? ''),
            ];
            $service = $services[(int) $this->text($row['serviceID'] ?? '0')] ?? null;
            if ($cookie['name'] === '') {
                continue;
            }

            if ($service === null || $this->text($row['isOwnDomainCookie'] ?? '') === '1') {
                $site[] = [
                    'name' => $cookie['name'],
                    'category' => $this->purposeCategory($this->text($row['purpose'] ?? '')),
                    'duration' => $cookie['duration'],
                    'purpose' => $cookie['purpose'],
                ];
                continue;
            }

            $vendors[\strtolower($service)][] = $cookie;
        }

        return ['site' => $site, 'vendors' => $vendors];
    }

    /**
     * The services with cookies and no custom vendor of the same name.
     *
     * @return list<string>
     */
    private function orphanServices(): array
    {
        $scripts = $this->storage->scripts();
        $vendors = \array_map(
            fn(array $entry): string => \strtolower($this->text($entry['name'] ?? '')),
            $this->entries($scripts['block_script'] ?? [])
        );
        $services = $this->storage->serviceNames();
        $orphans = [];
        foreach ($this->storage->cookies() as $row) {
            $service = $services[(int) $this->text($row['serviceID'] ?? '0')] ?? null;
            if ($service === null || $this->text($row['isOwnDomainCookie'] ?? '') === '1') {
                continue;
            }

            if (!\in_array(\strtolower($service), $vendors, true)) {
                $orphans[] = $service;
            }
        }

        return \array_values(\array_unique($orphans));
    }

    private function purposeCategory(string $purpose): string
    {
        $purpose = \strtolower($purpose);
        foreach (self::PURPOSES as $word => $category) {
            if (\str_contains($purpose, $word)) {
                return $category;
            }
        }

        return Category::NECESSARY;
    }

    /**
     * The category of Cookie Choices of a category of Complianz, functional is the necessary one.
     */
    private function category(mixed $category): string
    {
        $category = $this->text($category);

        return \in_array($category, [Category::PREFERENCES, Category::STATISTICS, Category::MARKETING], true)
            ? $category
            : Category::NECESSARY;
    }

    /**
     * The entries of a list of the script center turned on.
     *
     * @return list<array<array-key, mixed>>
     */
    private function entries(mixed $list): array
    {
        $entries = [];
        foreach (\is_array($list) ? $list : [] as $entry) {
            if (!\is_array($entry) || $this->text($entry['enable'] ?? '1') !== '1') {
                continue;
            }

            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    private function texts(mixed $values): array
    {
        $texts = \array_map($this->text(...), \is_array($values) ? $values : []);

        return \array_values(\array_filter($texts, static fn(string $text): bool => $text !== ''));
    }

    private function text(mixed $value): string
    {
        return \is_scalar($value) ? \trim((string) $value) : '';
    }
}
