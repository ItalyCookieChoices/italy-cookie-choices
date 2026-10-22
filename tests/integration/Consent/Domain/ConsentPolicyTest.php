<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\Domain;

use ItalyCookieChoices\Consent\Domain\Categories;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\Domain\Consent;
use ItalyCookieChoices\Consent\Domain\ConsentDuration;
use ItalyCookieChoices\Consent\Domain\ConsentPolicy;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentPolicyTest extends WPTestCase
{
    private const NOW = '2026-10-22T10:00:00+00:00';

    public function testTheDurationIsSixMonthsByDefault(): void
    {
        $this->assertSame(182, (new ConsentDuration())->days());
    }

    public function testTheDurationMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ConsentDuration(0);
    }

    public function testTheConsentExpiresAfterTheDuration(): void
    {
        $givenAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this->assertEquals(
            new \DateTimeImmutable('2026-01-11T00:00:00+00:00'),
            (new ConsentDuration(10))->expiresAt($givenAt)
        );
    }

    public function testThePolicyVersionCannotBeEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PolicyVersion(' ');
    }

    public function testPolicyVersionsAreComparedByValue(): void
    {
        $this->assertTrue((new PolicyVersion('2'))->equals(new PolicyVersion('2')));
        $this->assertFalse((new PolicyVersion('2'))->equals(new PolicyVersion('3')));
        $this->assertSame('2', (new PolicyVersion('2'))->toString());
    }

    public function testTheConsentKeepsTheChoice(): void
    {
        $givenAt = new \DateTimeImmutable(self::NOW);
        $consent = new Consent(['necessary', 'statistics'], new PolicyVersion('1'), $givenAt);

        $this->assertSame(['necessary', 'statistics'], $consent->categories());
        $this->assertTrue($consent->includes('statistics'));
        $this->assertFalse($consent->includes('marketing'));
        $this->assertSame('1', $consent->version()->toString());
        $this->assertSame($givenAt, $consent->givenAt());
    }

    public function testAConsentForTheCurrentVersionIsAccepted(): void
    {
        $this->assertTrue($this->policy()->accepts($this->consent(['statistics'], '1', '-1 day'), $this->now()));
    }

    public function testAConsentForAnotherVersionIsNotAccepted(): void
    {
        $this->assertFalse($this->policy()->accepts($this->consent(['statistics'], '0', '-1 day'), $this->now()));
    }

    public function testAnExpiredConsentIsNotAccepted(): void
    {
        $this->assertFalse($this->policy()->accepts($this->consent(['statistics'], '1', '-30 days'), $this->now()));
    }

    public function testAConsentIsAcceptedUntilTheLastSecond(): void
    {
        $this->assertTrue($this->policy()->accepts($this->consent([], '1', '-29 days -23 hours'), $this->now()));
        $this->assertFalse($this->policy()->accepts($this->consent([], '1', '-30 days'), $this->now()));
    }

    public function testTheRequiredCategoriesAreAllowedWithoutConsent(): void
    {
        $this->assertTrue($this->policy()->allows(null, 'necessary', $this->now()));
    }

    public function testTheOptionalCategoriesNeedAConsent(): void
    {
        $this->assertFalse($this->policy()->allows(null, 'statistics', $this->now()));
    }

    public function testTheAcceptedCategoriesAreAllowed(): void
    {
        $consent = $this->consent(['statistics'], '1', '-1 day');

        $this->assertTrue($this->policy()->allows($consent, 'statistics', $this->now()));
        $this->assertFalse($this->policy()->allows($consent, 'marketing', $this->now()));
    }

    public function testAnUnknownCategoryIsNotAllowedEvenIfTheConsentIncludesIt(): void
    {
        $consent = $this->consent(['social'], '1', '-1 day');

        $this->assertFalse($this->policy()->allows($consent, 'social', $this->now()));
    }

    public function testAnOutdatedConsentAllowsOnlyTheRequiredCategories(): void
    {
        $consent = $this->consent(['statistics'], '0', '-1 day');

        $this->assertFalse($this->policy()->allows($consent, 'statistics', $this->now()));
        $this->assertTrue($this->policy()->allows($consent, 'necessary', $this->now()));
    }

    public function testThePolicyExposesItsRules(): void
    {
        $policy = $this->policy();

        $this->assertSame(['necessary', 'statistics', 'marketing'], $policy->categories()->ids());
        $this->assertSame('1', $policy->version()->toString());
        $this->assertSame(30, $policy->duration()->days());
    }

    private function policy(): ConsentPolicy
    {
        return new ConsentPolicy(
            new Categories(new Category('necessary', true), new Category('statistics'), new Category('marketing')),
            new PolicyVersion('1'),
            new ConsentDuration(30)
        );
    }

    /**
     * @param list<string> $categories
     */
    private function consent(array $categories, string $version, string $givenAgo): Consent
    {
        return new Consent($categories, new PolicyVersion($version), $this->now()->modify($givenAgo));
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW);
    }
}
