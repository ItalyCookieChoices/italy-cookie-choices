<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\Borlabs;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Blocking\Domain\CustomScript;
use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Blocking\Domain\Vendors;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Cookies\Application\CookieSetting;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Migration\Application\Conversions;

/**
 * The service groups and the services of Borlabs Cookie.
 *
 * A service of a common vendor turns it on, its ids become the Google and Facebook tags. The other services
 * with a location become custom vendors with their cookies, and their opt-in code a script after the consent.
 * The services of the essential group are the ones of the site.
 */
final readonly class BorlabsServices
{
    /**
     * The service groups of Borlabs Cookie, as the categories. The custom ones are marketing.
     */
    private const GROUPS = [
        'essential' => Category::NECESSARY,
        'statistics' => Category::STATISTICS,
        'marketing' => Category::MARKETING,
        'external-media' => Category::MARKETING,
    ];

    /**
     * The keys of the services of Borlabs Cookie with a common vendor.
     */
    private const PRESETS = [
        'google-analytics' => VendorId::GOOGLE_ANALYTICS,
        'google-tag-manager' => VendorId::GOOGLE_TAG_MANAGER,
        'google-ads' => VendorId::GOOGLE_ADS,
        'meta-pixel' => VendorId::FACEBOOK,
        'facebook' => VendorId::FACEBOOK,
        'microsoft-advertising' => VendorId::MICROSOFT_ADVERTISING,
        'microsoft-clarity' => VendorId::MICROSOFT_CLARITY,
        'tiktok-pixel' => VendorId::TIKTOK,
        'hotjar' => VendorId::HOTJAR,
        'hubspot' => VendorId::HUBSPOT,
        'youtube' => VendorId::YOUTUBE,
        'vimeo' => VendorId::VIMEO,
        'google-maps' => VendorId::GOOGLE_MAPS,
        'instagram' => VendorId::INSTAGRAM,
        'x-alias-twitter' => VendorId::X,
        'linkedin' => VendorId::LINKEDIN,
    ];

    /**
     * The settings fields of the services with the ids of the tags the plugin prints.
     */
    private const TAGS = [
        IntegrationSetting::GA_MEASUREMENT_ID => 'google-analytics-tracking-id',
        IntegrationSetting::GTM_CONTAINER_ID => 'google-tag-manager-id',
        IntegrationSetting::FACEBOOK_PIXEL_ID => 'pixel-id',
    ];

    private const CONSENT_MODE_FIELDS = ['google-analytics-consent-mode', 'google-tag-manager-cm-active'];

    /**
     * The placeholders of the settings in the opt-in code, filled by Borlabs Cookie.
     */
    private const PLACEHOLDER = '{{';

    public function __construct(
        private BorlabsStorage $storage,
        private Conversions $conversions,
        private VendorsFactory $vendors,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $groups = $this->groups();
        $services = $this->services($groups['ids']);

        return [
            ConsentSetting::CATEGORIES => $groups['texts'],
            CookieSetting::SITE_COOKIES => $services['site'],
            BlockingSetting::CUSTOM_VENDORS => $services['vendors'],
            BlockingSetting::CUSTOM_SCRIPTS => $services['scripts'],
            ...$this->tags(),
        ];
    }

    /**
     * @return list<string>
     */
    public function vendorsInUse(): array
    {
        $vendors = [];
        foreach ($this->storage->rows('services', true) as $service) {
            $vendor = self::PRESETS[$this->conversions->text($service['key'] ?? '')] ?? null;
            if ($vendor !== null && $this->conversions->isOn($service['status'] ?? '1')) {
                $vendors[] = $vendor;
            }
        }

        return \array_values(\array_unique($vendors));
    }

    /**
     * @return list<string>
     */
    public function notes(): array
    {
        $skipped = $this->services($this->groups()['ids'])['skipped'];
        if ($skipped === []) {
            return [];
        }

        return [\sprintf(
            /* translators: %s: the names of the services, separated by commas. */
            \__(
                'These services are not imported, they have no location or their code needs Borlabs Cookie: %s.',
                'italy-cookie-choices'
            ),
            \implode(', ', $skipped)
        )];
    }

    /**
     * The texts of each category, the first group wins, and the category of each group id.
     *
     * @return array{texts: array<string, array{label: string, description: string}>, ids: array<int, string>}
     */
    private function groups(): array
    {
        $texts = [];
        $ids = [];
        foreach ($this->storage->rows('service_groups', true) as $group) {
            $category = self::GROUPS[$this->conversions->text($group['key'] ?? '')] ?? Category::MARKETING;
            $ids[(int) $this->conversions->text($group['id'] ?? '0')] = $category;
            $texts[$category] ??= [
                'label' => $this->conversions->text($group['name'] ?? ''),
                'description' => \wp_strip_all_tags($this->conversions->text($group['description'] ?? '')),
            ];
        }

        return ['texts' => $texts, 'ids' => $ids];
    }

    /**
     * @param array<int, string> $groups
     * @return array{
     *     site: list<array{name: string, category: string, duration: string, purpose: string}>,
     *     vendors: list<array<string, mixed>>,
     *     scripts: list<array{name: string, category: string, position: string, code: string}>,
     *     skipped: list<string>
     * }
     */
    private function services(array $groups): array
    {
        $cookies = $this->byService('service_cookies');
        $locations = $this->byService('service_locations');
        $known = $this->vendors->known();
        $result = ['site' => [], 'vendors' => [], 'scripts' => [], 'skipped' => []];
        foreach ($this->storage->rows('services', true) as $service) {
            $id = (int) $this->conversions->text($service['id'] ?? '0');
            $key = $this->conversions->text($service['key'] ?? '');
            $name = $this->conversions->text($service['name'] ?? '');
            $group = (int) $this->conversions->text($service['service_group_id'] ?? '0');
            $category = $groups[$group] ?? Category::MARKETING;
            $serviceCookies = \array_map($this->cookie(...), $cookies[$id] ?? []);
            $patterns = \array_map($this->pattern(...), $locations[$id] ?? []);
            if ($category === Category::NECESSARY) {
                foreach ($serviceCookies as $cookie) {
                    $result['site'][] = [
                        'name' => $cookie['name'],
                        'category' => $category,
                        ...\array_slice($cookie, 1),
                    ];
                }

                continue;
            }

            if (\array_key_exists($key, self::PRESETS) || $this->isKnown($known, $patterns)) {
                continue;
            }

            $code = $this->conversions->text($service['opt_in_code'] ?? '');
            if ($patterns === [] && ($code === '' || \str_contains($code, self::PLACEHOLDER))) {
                $result['skipped'][] = $name;
                continue;
            }

            if ($patterns !== []) {
                $vendor = [
                    'id' => $this->conversions->id($key !== '' ? $key : $name),
                    'name' => $name,
                    'category' => $category,
                    'patterns' => $patterns,
                ];
                $result['vendors'][] = $serviceCookies === [] ? $vendor : [...$vendor, 'cookies' => $serviceCookies];
            }

            if ($code !== '' && !\str_contains($code, self::PLACEHOLDER)) {
                $result['scripts'][] = [
                    'name' => $name,
                    'category' => $category,
                    'position' => CustomScript::BODY,
                    'code' => $code,
                ];
            }
        }

        return $result;
    }

    /**
     * @param list<string> $patterns
     */
    private function isKnown(Vendors $known, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($known->loading('https://' . $pattern) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * The ids of the tags in the settings fields of the services, and Consent Mode.
     *
     * @return array<string, string>
     */
    private function tags(): array
    {
        $fields = [];
        foreach ($this->storage->rows('services', true) as $service) {
            $decoded = \json_decode($this->conversions->text($service['settings_fields'] ?? ''), true);
            $list = \is_array($decoded) && \is_array($decoded['list'] ?? null) ? $decoded['list'] : [];
            foreach ($list as $field) {
                if (\is_array($field) && \is_string($field['key'] ?? null)) {
                    $fields[$field['key']] = $this->conversions->text($field['value'] ?? '');
                }
            }
        }

        $tags = [];
        foreach (self::TAGS as $setting => $field) {
            $tags[$setting] = $fields[$field] ?? '';
        }

        foreach (self::CONSENT_MODE_FIELDS as $field) {
            if ($this->conversions->isOn($fields[$field] ?? '')) {
                $tags[IntegrationSetting::CONSENT_MODE] = '1';
            }
        }

        return $tags;
    }

    /**
     * The rows of a table of the services, by service id.
     *
     * @return array<int, list<array<string, mixed>>>
     */
    private function byService(string $table): array
    {
        $rows = [];
        foreach ($this->storage->rows($table, false) as $row) {
            $rows[(int) $this->conversions->text($row['service_id'] ?? '0')][] = $row;
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{name: string, duration: string, purpose: string}
     */
    private function cookie(array $row): array
    {
        return [
            'name' => $this->conversions->text($row['name'] ?? ''),
            'duration' => $this->conversions->text($row['lifetime'] ?? ''),
            'purpose' => \wp_strip_all_tags($this->conversions->text($row['description'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function pattern(array $row): string
    {
        return $this->conversions->text($row['hostname'] ?? '') . $this->conversions->text($row['path'] ?? '');
    }
}
