<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\Infrastructure;

use ItalyCookieChoices\Consent\Infrastructure\FilteredConsentPolicy;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class FilteredConsentPolicyTest extends WPTestCase
{
    public function testTheDefaultPolicy(): void
    {
        $policy = (new FilteredConsentPolicy())->create();

        $this->assertSame(['necessary', 'preferences', 'statistics', 'marketing'], $policy->categories()->ids());
        $this->assertSame(['necessary'], $policy->categories()->requiredIds());
        $this->assertSame('1', $policy->version()->toString());
        $this->assertSame(182, $policy->duration()->days());
    }

    public function testTheCategoriesCanBeFiltered(): void
    {
        \add_filter(FilteredConsentPolicy::CATEGORIES_FILTER, static fn(array $categories): array => [
            ['id' => 'statistics'],
            ['id' => 'social', 'required' => true],
        ]);

        $categories = (new FilteredConsentPolicy())->create()->categories();

        $this->assertSame(['necessary', 'statistics', 'social'], $categories->ids());
        $this->assertSame(['necessary', 'social'], $categories->requiredIds());
    }

    public function testTheNecessaryCategoryStaysRequired(): void
    {
        \add_filter(FilteredConsentPolicy::CATEGORIES_FILTER, static fn(): array => [
            ['id' => 'necessary', 'required' => false],
        ]);

        $this->assertSame(['necessary'], (new FilteredConsentPolicy())->create()->categories()->requiredIds());
    }

    public function testTheInvalidCategoriesAreSkipped(): void
    {
        \add_filter(FilteredConsentPolicy::CATEGORIES_FILTER, static fn(): array => [
            'marketing',
            ['required' => true],
            ['id' => 'Not Valid'],
            ['id' => 'statistics', 'required' => 'yes'],
            ['id' => 'statistics'],
        ]);

        $categories = (new FilteredConsentPolicy())->create()->categories();

        $this->assertSame(['necessary', 'statistics'], $categories->ids());
        $this->assertSame(['necessary'], $categories->requiredIds());
    }

    public function testAFilterThatReturnsNoListKeepsTheNecessaryCategory(): void
    {
        \add_filter(FilteredConsentPolicy::CATEGORIES_FILTER, '__return_null');

        $this->assertSame(['necessary'], (new FilteredConsentPolicy())->create()->categories()->ids());
    }

    public function testThePolicyVersionCanBeFiltered(): void
    {
        \add_filter(FilteredConsentPolicy::VERSION_FILTER, static fn(): int => 3);

        $this->assertSame('3', (new FilteredConsentPolicy())->create()->version()->toString());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public function invalidVersions(): iterable
    {
        yield 'empty' => [''];
        yield 'blank' => [' '];
        yield 'array' => [['2']];
        yield 'null' => [null];
    }

    /**
     * @dataProvider invalidVersions
     */
    public function testAnInvalidPolicyVersionFallsBackToTheDefault(mixed $version): void
    {
        \add_filter(FilteredConsentPolicy::VERSION_FILTER, static fn(): mixed => $version);

        $this->assertSame('1', (new FilteredConsentPolicy())->create()->version()->toString());
    }

    public function testTheDurationCanBeFiltered(): void
    {
        \add_filter(FilteredConsentPolicy::DURATION_FILTER, static fn(): int => 30);

        $this->assertSame(30, (new FilteredConsentPolicy())->create()->duration()->days());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public function invalidDurations(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'numeric string' => ['30'];
    }

    /**
     * @dataProvider invalidDurations
     */
    public function testAnInvalidDurationFallsBackToSixMonths(mixed $days): void
    {
        \add_filter(FilteredConsentPolicy::DURATION_FILTER, static fn(): mixed => $days);

        $this->assertSame(182, (new FilteredConsentPolicy())->create()->duration()->days());
    }
}
