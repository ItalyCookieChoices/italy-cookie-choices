<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\RealCookieBanner;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Blocking\Domain\CustomScript;
use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Blocking\Domain\Vendors;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Cookies\Application\CookieDurations;
use ItalyCookieChoices\Cookies\Application\CookieSetting;
use ItalyCookieChoices\Integrations\Application\IntegrationSetting;
use ItalyCookieChoices\Migration\Application\Conversions;

/**
 * The service groups, the services and the content blockers of Real Cookie Banner.
 *
 * A service of a common vendor turns it on: found by its template, or by its code and the rules of its blockers
 * that the vendor matches. The ids of the Google and Facebook tags in its code become the tags the plugin prints,
 * the code of the other vendors a script after the consent. The other services become custom vendors with the
 * rules of their blockers and their cookies, and their code a script after the consent. The services of the
 * essential group are the ones of the site.
 *
 * @phpstan-type Cookie array{name: string, duration: string, purpose: string}
 */
final readonly class RealCookieBannerServices
{
    /**
     * The default groups of Real Cookie Banner, by their order: essential, functional, statistics, marketing.
     * The custom groups are marketing.
     */
    private const GROUPS = [1 => Category::PREFERENCES, 2 => Category::STATISTICS, 3 => Category::MARKETING];

    /**
     * The templates of Real Cookie Banner of a common vendor, the others are found by their code and rules.
     */
    private const PRESETS = [
        'google-analytics-analytics-4' => VendorId::GOOGLE_ANALYTICS,
        'google-analytics-ua' => VendorId::GOOGLE_ANALYTICS,
        'monsterinsights-analytics-4' => VendorId::GOOGLE_ANALYTICS,
        'exact-metrics-analytics-4' => VendorId::GOOGLE_ANALYTICS,
        'gtm' => VendorId::GOOGLE_TAG_MANAGER,
        'google-ads-conversion-tracking' => VendorId::GOOGLE_ADS,
        'google-adsense' => VendorId::GOOGLE_ADSENSE,
        'youtube' => VendorId::YOUTUBE,
        'vimeo' => VendorId::VIMEO,
        'google-maps' => VendorId::GOOGLE_MAPS,
        'hubspot-chat' => VendorId::HUBSPOT,
        'solid-security-with-google-recaptcha' => VendorId::GOOGLE_RECAPTCHA,
    ];

    /** The service of Real Cookie Banner itself, its consent cookie is replaced by the one of the plugin. */
    private const ITSELF = 'real-cookie-banner';

    /**
     * The ids of the tags the plugin prints, as they appear in the code of the services.
     */
    private const TAGS = [
        IntegrationSetting::GA_MEASUREMENT_ID => '/\b(G-[A-Z0-9]{4,})\b/',
        IntegrationSetting::GTM_CONTAINER_ID => '/\b(GTM-[A-Z0-9]{4,})\b/',
        IntegrationSetting::FACEBOOK_PIXEL_ID => '/fbq\(\s*[\'"]init[\'"]\s*,\s*[\'"](\d+)[\'"]/',
    ];

    private const TAG_VENDORS = [
        VendorId::GOOGLE_ANALYTICS,
        VendorId::GOOGLE_TAG_MANAGER,
        VendorId::FACEBOOK,
    ];

    /**
     * The fields of the code, replaced by Real Cookie Banner with the values of the service, like {{measurementId}}.
     */
    private const FIELD = '/\{\{\s*([A-Za-z0-9_-]+)\s*\}\}/';
    private const PLACEHOLDER = '{{';

    /** The type of the technical definitions that are cookies, not local or session storage. */
    private const HTTP_COOKIE = 'http';

    public function __construct(
        private RealCookieBannerStorage $storage,
        private Conversions $conversions,
        private VendorsFactory $vendors,
        private CookieDurations $durations,
    ) {
    }

    /**
     * @return array{
     *     settings: array<string, mixed>,
     *     vendorsInUse: list<string>,
     *     skipped: list<string>
     * }
     */
    public function read(): array
    {
        $groups = $this->groups();
        $rules = $this->rulesByService();
        $known = $this->vendors->known();
        $result = ['site' => [], 'vendors' => [], 'scripts' => [], 'inUse' => [], 'tags' => [], 'skipped' => []];
        foreach ($this->storage->posts(RealCookieBannerStorage::SERVICES) as $service) {
            $result = $this->withService(
                $result,
                $service,
                $groups['ids'][$this->storage->groupOf($service['id'])] ?? Category::MARKETING,
                $rules[$service['id']] ?? [],
                $known
            );
        }

        return [
            'settings' => [
                ConsentSetting::CATEGORIES => $groups['texts'],
                CookieSetting::SITE_COOKIES => $result['site'],
                BlockingSetting::CUSTOM_VENDORS => $result['vendors'],
                BlockingSetting::CUSTOM_SCRIPTS => $result['scripts'],
                ...$result['tags'],
            ],
            'vendorsInUse' => \array_values(\array_unique($result['inUse'])),
            'skipped' => $result['skipped'],
        ];
    }

    /**
     * @param array{
     *     site: list<array<string, string>>,
     *     vendors: list<array<string, mixed>>,
     *     scripts: list<array<string, string>>,
     *     inUse: list<string>,
     *     tags: array<string, string>,
     *     skipped: list<string>
     * } $result
     * @param array{id: int, title: string, content: string} $service
     * @param list<string> $patterns
     * @return array{
     *     site: list<array<string, string>>,
     *     vendors: list<array<string, mixed>>,
     *     scripts: list<array<string, string>>,
     *     inUse: list<string>,
     *     tags: array<string, string>,
     *     skipped: list<string>
     * }
     */
    private function withService(
        array $result,
        array $service,
        string $category,
        array $patterns,
        Vendors $known
    ): array {
        $preset = $this->conversions->text($this->storage->meta($service['id'], 'presetId'));
        if ($preset === self::ITSELF) {
            return $result;
        }

        $cookies = $this->cookies($service['id']);
        $code = $this->code($service['id']);
        if ($category === Category::NECESSARY) {
            foreach ($cookies as $cookie) {
                $result['site'][] = ['name' => $cookie['name'], 'category' => $category, ...\array_slice($cookie, 1)];
            }

            return $result;
        }

        $vendor = self::PRESETS[$preset] ?? $this->knownVendor($known, $code, $patterns);
        if ($vendor !== null) {
            $result['inUse'][] = $vendor;
            $tags = $this->tags($code);
            $result['tags'] = [...$result['tags'], ...$tags];
            if (\in_array($vendor, self::TAG_VENDORS, true) && $tags !== []) {
                return $result;
            }

            $result['scripts'] = $this->withScript($result['scripts'], $service['title'], $category, $code);

            return $result;
        }

        if ($patterns === [] && !$this->isUsable($code)) {
            $result['skipped'][] = $service['title'];

            return $result;
        }

        if ($patterns !== []) {
            $custom = [
                'id' => $this->conversions->id($service['title']),
                'name' => $service['title'],
                'category' => $category,
                'patterns' => $patterns,
            ];
            $result['vendors'][] = $cookies === [] ? $custom : [...$custom, 'cookies' => $cookies];
        }

        $result['scripts'] = $this->withScript($result['scripts'], $service['title'], $category, $code);

        return $result;
    }

    /**
     * @param list<array<string, string>> $scripts
     * @return list<array<string, string>>
     */
    private function withScript(array $scripts, string $name, string $category, string $code): array
    {
        if (!$this->isUsable($code)) {
            return $scripts;
        }

        $scripts[] = [
            'name' => $name,
            'category' => $category,
            'position' => CustomScript::BODY,
            'code' => $code,
        ];

        return $scripts;
    }

    private function isUsable(string $code): bool
    {
        return $code !== '' && !\str_contains($code, self::PLACEHOLDER);
    }

    /**
     * The common vendor of the code or of the rules of the blockers of a service, null for none.
     *
     * @param list<string> $patterns
     */
    private function knownVendor(Vendors $known, string $code, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            $vendor = $known->loading('https://' . \ltrim($pattern, '/'));
            if ($vendor !== null) {
                return $vendor->id();
            }
        }

        return $code === '' ? null : $known->running($code)?->id();
    }

    /**
     * The ids of the tags the plugin prints, from the code of a service.
     *
     * @return array<string, string>
     */
    private function tags(string $code): array
    {
        $tags = [];
        foreach (self::TAGS as $setting => $pattern) {
            if (\preg_match($pattern, $code, $match) === 1) {
                $tags[$setting] = $match[1];
            }
        }

        return $tags;
    }

    /**
     * The opt-in code of a service with its fields filled, like Real Cookie Banner does.
     */
    private function code(int $service): string
    {
        $code = $this->conversions->text($this->storage->meta($service, 'codeOptIn'));
        $fields = \json_decode($this->conversions->text($this->storage->meta($service, 'codeDynamics')), true);
        $fields = \is_array($fields) ? $fields : [];

        return (string) \preg_replace_callback(
            self::FIELD,
            fn(array $match): string => \array_key_exists($match[1], $fields)
                ? $this->conversions->text($fields[$match[1]])
                : $match[0],
            $code
        );
    }

    /**
     * The cookies of a service, without the local and the session storage.
     *
     * @return list<Cookie>
     */
    private function cookies(int $service): array
    {
        $json = $this->conversions->text($this->storage->meta($service, 'technicalDefinitions'));
        $definitions = \json_decode($json, true);
        $cookies = [];
        foreach (\is_array($definitions) ? $definitions : [] as $definition) {
            if (!\is_array($definition) || ($definition['type'] ?? self::HTTP_COOKIE) !== self::HTTP_COOKIE) {
                continue;
            }

            $name = $this->conversions->text($definition['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $cookies[] = [
                'name' => $name,
                'duration' => $this->duration($definition),
                'purpose' => \wp_strip_all_tags($this->conversions->text($definition['purpose'] ?? '')),
            ];
        }

        return $cookies;
    }

    /**
     * @param array<array-key, mixed> $definition
     */
    private function duration(array $definition): string
    {
        if ($this->conversions->isOn($definition['isSessionDuration'] ?? false)) {
            return $this->durations->session();
        }

        $duration = (int) $this->conversions->text($definition['duration'] ?? '0');
        if ($duration <= 0) {
            return '';
        }

        return match ($this->conversions->text($definition['durationUnit'] ?? '')) {
            'y' => $this->durations->years($duration),
            'mo' => $this->durations->months($duration),
            'd' => $this->durations->days($duration),
            'h' => $this->hours($duration),
            'm' => $this->durations->minutes($duration),
            's' => $this->durations->minutes(\max(1, \intdiv($duration, 60))),
            default => '',
        };
    }

    private function hours(int $hours): string
    {
        return $hours % 24 === 0 ? $this->durations->days(\intdiv($hours, 24)) : $this->durations->minutes($hours * 60);
    }

    /**
     * The address rules of the published blockers, by the services they block: the HTML selectors are skipped.
     *
     * @return array<int, list<string>>
     */
    private function rulesByService(): array
    {
        $rules = [];
        foreach ($this->storage->posts(RealCookieBannerStorage::BLOCKERS) as $blocker) {
            $patterns = $this->patterns($this->conversions->text($this->storage->meta($blocker['id'], 'rules')));
            $services = \explode(',', $this->conversions->text($this->storage->meta($blocker['id'], 'services')));
            foreach (\array_filter(\array_map('intval', $services)) as $service) {
                $rules[$service] = \array_values(\array_unique([...$rules[$service] ?? [], ...$patterns]));
            }
        }

        return $rules;
    }

    /**
     * The addresses of the rules of a blocker, one for each line, without the * of the wildcards.
     *
     * @return list<string>
     */
    private function patterns(string $rules): array
    {
        $patterns = [];
        foreach (\preg_split('/\R/', $rules) ?: [] as $rule) {
            $pattern = \trim(\str_replace('*', '', \trim($rule)), '/');
            if ($pattern === '' || \preg_match('/[\s\[\]"=<>]/', $pattern) === 1 || !\str_contains($pattern, '.')) {
                continue;
            }

            $patterns[] = $pattern;
        }

        return $patterns;
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
        foreach ($this->storage->groups() as $group) {
            $category = $group['essential']
                ? Category::NECESSARY
                : ($group['default'] ? self::GROUPS[$group['order']] ?? Category::MARKETING : Category::MARKETING);
            $ids[$group['id']] = $category;
            $texts[$category] ??= [
                'label' => $group['name'],
                'description' => \wp_strip_all_tags($group['description']),
            ];
        }

        return ['texts' => $texts, 'ids' => $ids];
    }
}
