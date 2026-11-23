<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;

/**
 * The scan assistant: it asks the AI provider of the site owner a vendor name, a category and a purpose
 * for the unknown third parties and cookies the scans found.
 *
 * It sends only the host names, the cookie names, the categories and the common vendors,
 * never the data of the visitors or the consent log. Nothing is saved: the admin accepts each suggestion.
 *
 * @phpstan-type HostSuggestion array{host: string, name: string, category: string, purpose: string, vendor: string}
 * @phpstan-type CookieSuggestion array{name: string, provider: string, category: string, purpose: string}
 */
final readonly class ScanAssistant
{
    /** The hosts and the cookies of a request, at most. */
    public const LIMIT = 50;

    private const MAX_LENGTH = 200;
    private const INSTRUCTION = 'You help the owner of a WordPress site to write its cookie policy. '
        . 'For each third-party host, suggest the name of the company or service, the consent category of its scripts '
        . 'and their purpose. When the host belongs to one of the common vendors, set vendor to its id, '
        . 'otherwise leave vendor empty. For each cookie, suggest who sets it, its consent category and its purpose. '
        . 'Use only the category ids given. Write the names and the purposes in the language of the locale, '
        . 'each purpose in one short sentence. When you do not know a host or a cookie, leave it out.';

    public function __construct(
        private TextGenerator $generator,
        private VendorPresets $presets,
        private ConsentPolicyFactory $policy,
        private AssistantUsage $usage,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->generator->isAvailable();
    }

    /**
     * @param list<string> $hosts The unknown third parties.
     * @param list<string> $cookies The names of the unknown cookies.
     * @return array{hosts: list<HostSuggestion>, cookies: list<CookieSuggestion>}
     * @throws \RuntimeException When the provider fails.
     */
    public function suggest(array $hosts, array $cookies): array
    {
        $hosts = $this->limited($hosts);
        $cookies = $this->limited($cookies);
        if ($hosts === [] && $cookies === []) {
            return ['hosts' => [], 'cookies' => []];
        }

        $categories = $this->policy->create()->categories();
        $reply = $this->generator->json(
            self::INSTRUCTION,
            (string) \wp_json_encode([
                'locale' => \get_locale(),
                'categories' => $categories->ids(),
                'vendors' => $this->vendors(),
                'hosts' => $hosts,
                'cookies' => $cookies,
            ]),
            $this->schema($categories->optionalIds(), $categories->ids())
        );
        $this->usage->record();

        return [
            'hosts' => $this->hostSuggestions($reply['hosts'] ?? [], $hosts, $categories->optionalIds()),
            'cookies' => $this->cookieSuggestions($reply['cookies'] ?? [], $cookies, $categories->ids()),
        ];
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private function limited(array $values): array
    {
        $values = \array_filter(\array_map('trim', $values), static fn(string $value): bool => $value !== '');

        return \array_slice(\array_values(\array_unique($values)), 0, self::LIMIT);
    }

    /**
     * @return list<array{id: string, name: string, category: string}>
     */
    private function vendors(): array
    {
        $vendors = [];
        foreach ($this->presets->all() as $vendor) {
            $vendors[] = [
                'id' => $vendor->id(),
                'name' => $this->presets->name($vendor->id()),
                'category' => $vendor->category(),
            ];
        }

        return $vendors;
    }

    /**
     * @param list<string> $vendorCategories
     * @param list<string> $cookieCategories
     * @return array<string, mixed>
     */
    private function schema(array $vendorCategories, array $cookieCategories): array
    {
        $text = ['type' => 'string'];

        return [
            'type' => 'object',
            'properties' => [
                'hosts' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'host' => $text,
                            'name' => $text,
                            'category' => ['type' => 'string', 'enum' => $vendorCategories],
                            'purpose' => $text,
                            'vendor' => $text,
                        ],
                        'required' => ['host', 'name', 'category', 'purpose', 'vendor'],
                    ],
                ],
                'cookies' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => $text,
                            'provider' => $text,
                            'category' => ['type' => 'string', 'enum' => $cookieCategories],
                            'purpose' => $text,
                        ],
                        'required' => ['name', 'provider', 'category', 'purpose'],
                    ],
                ],
            ],
            'required' => ['hosts', 'cookies'],
        ];
    }

    /**
     * Only the hosts asked, in a category the visitors can refuse, the vendor only when it is a common one.
     *
     * @param list<string> $hosts
     * @param list<string> $categories
     * @return list<HostSuggestion>
     */
    private function hostSuggestions(mixed $reply, array $hosts, array $categories): array
    {
        $vendors = \array_column($this->vendors(), 'id');
        $suggestions = [];
        foreach ($this->records($reply) as $record) {
            $host = $this->text($record, 'host');
            $category = $this->text($record, 'category');
            if (!\in_array($host, $hosts, true) || !\in_array($category, $categories, true)) {
                continue;
            }

            $vendor = $this->text($record, 'vendor');
            $suggestions[$host] = [
                'host' => $host,
                'name' => $this->text($record, 'name'),
                'category' => $category,
                'purpose' => $this->text($record, 'purpose'),
                'vendor' => \in_array($vendor, $vendors, true) ? $vendor : '',
            ];
        }

        return \array_values($suggestions);
    }

    /**
     * Only the cookies asked, in a category of the policy.
     *
     * @param list<string> $cookies
     * @param list<string> $categories
     * @return list<CookieSuggestion>
     */
    private function cookieSuggestions(mixed $reply, array $cookies, array $categories): array
    {
        $suggestions = [];
        foreach ($this->records($reply) as $record) {
            $name = $this->text($record, 'name');
            $category = $this->text($record, 'category');
            if (!\in_array($name, $cookies, true) || !\in_array($category, $categories, true)) {
                continue;
            }

            $suggestions[$name] = [
                'name' => $name,
                'provider' => $this->text($record, 'provider'),
                'category' => $category,
                'purpose' => $this->text($record, 'purpose'),
            ];
        }

        return \array_values($suggestions);
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    private function records(mixed $reply): array
    {
        return \array_values(\array_filter(\is_array($reply) ? $reply : [], \is_array(...)));
    }

    /**
     * A plain text of the reply, short, without the HTML a model could write.
     *
     * @param array<array-key, mixed> $record
     */
    private function text(array $record, string $key): string
    {
        $value = $record[$key] ?? '';

        return \is_string($value) ? \mb_substr(\sanitize_text_field($value), 0, self::MAX_LENGTH) : '';
    }
}
