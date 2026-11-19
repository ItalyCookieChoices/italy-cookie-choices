<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure\CookieYes;

use ItalyCookieChoices\Blocking\Application\BlockingSetting;
use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Consent\Application\ConsentSetting;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Cookies\Application\CookieSetting;
use ItalyCookieChoices\Migration\Application\ArrayReader;
use ItalyCookieChoices\Migration\Application\Conversions;

/**
 * The categories and the cookies of CookieYes 3.
 *
 * A cookie with a script pattern is blocked: the pattern of a common vendor turns that vendor on, the others
 * become custom vendors with their cookies. The cookies without a pattern are the cookies of the site.
 */
final readonly class CookieYesCookies
{
    /**
     * The categories of CookieYes, by slug, as the categories of Cookie Choices. The others are marketing.
     */
    private const CATEGORIES = [
        'necessary' => Category::NECESSARY,
        'functional' => Category::PREFERENCES,
        'analytics' => Category::STATISTICS,
        'performance' => Category::STATISTICS,
        'advertisement' => Category::MARKETING,
    ];

    /**
     * The cookies of CookieYes itself, Cookie Choices has its own consent cookie.
     */
    private const OWN_COOKIES = 'cookieyes-';

    private const DEFAULT_LANGUAGE = 'en';

    public function __construct(
        private CookieYesStorage $storage,
        private Conversions $conversions,
        private VendorsFactory $vendors,
        private ArrayReader $reader,
    ) {
    }

    /**
     * The category of Cookie Choices of a slug of CookieYes, also of the versions 2.
     */
    public static function category(string $slug): string
    {
        return self::CATEGORIES[$slug] ?? Category::MARKETING;
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $categories = $this->categories();
        $cookies = $this->cookies($categories['ids']);

        return \array_filter(
            [
                ConsentSetting::CATEGORIES => $categories['texts'],
                CookieSetting::SITE_COOKIES => $cookies['site'],
                BlockingSetting::CUSTOM_VENDORS => $cookies['vendors'],
            ],
            static fn(array $value): bool => $value !== []
        );
    }

    /**
     * The common vendors of the patterns of the cookies.
     *
     * @return list<string>
     */
    public function vendorsInUse(): array
    {
        $known = $this->vendors->known();
        $vendors = [];
        foreach ($this->storage->cookies() as $cookie) {
            $vendor = $cookie['pattern'] === '' ? null : $known->loading($cookie['pattern']);
            if ($vendor !== null) {
                $vendors[] = $vendor->id();
            }
        }

        return \array_values(\array_unique($vendors));
    }

    /**
     * The texts of each category of Cookie Choices, the first category of CookieYes wins, and the category
     * of each category id.
     *
     * @return array{texts: array<string, array{label: string, description: string}>, ids: array<int, string>}
     */
    private function categories(): array
    {
        $texts = [];
        $ids = [];
        foreach ($this->storage->categories() as $row) {
            $category = self::category($row['slug']);
            $ids[$row['id']] = $category;
            $texts[$category] ??= [
                'label' => $this->localized($row['name']),
                'description' => \wp_strip_all_tags($this->localized($row['description'])),
            ];
        }

        return ['texts' => $texts, 'ids' => $ids];
    }

    /**
     * @param array<int, string> $categories
     * @return array{
     *     site: list<array{name: string, category: string, duration: string, purpose: string}>,
     *     vendors: list<array<string, mixed>>
     * }
     */
    private function cookies(array $categories): array
    {
        $known = $this->vendors->known();
        $site = [];
        $vendors = [];
        foreach ($this->storage->cookies() as $row) {
            if ($row['name'] === '' || \str_starts_with($row['name'], self::OWN_COOKIES)) {
                continue;
            }

            $cookie = [
                'name' => $row['name'],
                'duration' => $this->localized($row['duration']),
                'purpose' => \wp_strip_all_tags($this->localized($row['description'])),
            ];
            $category = $categories[$row['category']] ?? Category::NECESSARY;
            if ($row['pattern'] === '') {
                $site[] = ['name' => $cookie['name'], 'category' => $category, ...\array_slice($cookie, 1)];
                continue;
            }

            if ($known->loading($row['pattern']) !== null) {
                continue;
            }

            $vendors[$row['pattern']] ??= $this->vendor($row['pattern'], $category);
            $vendors[$row['pattern']]['cookies'][] = $cookie;
        }

        return ['site' => $site, 'vendors' => \array_values($vendors)];
    }

    /**
     * @return array{id: string, name: string, category: string, patterns: list<string>, cookies: list<mixed>}
     */
    private function vendor(string $pattern, string $category): array
    {
        $host = \explode('/', $pattern)[0];

        return [
            'id' => $this->conversions->id($host),
            'name' => $host,
            'category' => $category,
            'patterns' => [$pattern],
            'cookies' => [],
        ];
    }

    /**
     * The text in the default language, or the first one.
     *
     * @param array<array-key, mixed> $texts
     */
    private function localized(array $texts): string
    {
        $text = $texts[$this->reader->text($this->storage->settings(), 'languages', 'default')]
            ?? $texts[self::DEFAULT_LANGUAGE]
            ?? \reset($texts);

        return \is_scalar($text) ? \trim((string) $text) : '';
    }
}
