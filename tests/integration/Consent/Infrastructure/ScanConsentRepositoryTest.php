<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\Infrastructure;

use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Domain\ConsentRepository;
use ItalyCookieChoices\Consent\Infrastructure\CookieConsentRepository;
use ItalyCookieChoices\Consent\Infrastructure\ScanConsentRepository;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Scan\BuildsScanRequests;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScanConsentRepositoryTest extends WPTestCase
{
    use BuildsContainer;
    use BuildsScanRequests;

    public function testItIsTheConsentOfThePlugin(): void
    {
        $this->assertInstanceOf(ScanConsentRepository::class, $this->service(ConsentRepository::class));
    }

    public function testAPageInScanModeAcceptsEveryCategory(): void
    {
        $consent = $this->makeSut([], isScan: true)->current();

        $this->assertNotNull($consent);
        $this->assertSame(['necessary', 'preferences', 'statistics', 'marketing'], $consent->categories());
        $this->assertSame('1', $consent->version()->toString());
        $policy = $this->service(ConsentPolicyFactory::class)->create();
        $this->assertTrue($policy->accepts($consent, new \DateTimeImmutable()));
    }

    public function testAnotherPageReadsTheConsentCookie(): void
    {
        $cookie = ['version' => '1', 'categories' => ['necessary'], 'date' => '2026-11-01T10:00:00.000Z'];

        $consent = $this->makeSut([CookieConsentRepository::COOKIE_NAME => \wp_json_encode($cookie)])->current();

        $this->assertNotNull($consent);
        $this->assertSame(['necessary'], $consent->categories());
        $this->assertNull($this->makeSut([])->current());
    }

    /**
     * @param array<string, mixed> $cookies
     */
    private function makeSut(array $cookies, bool $isScan = false): ScanConsentRepository
    {
        return new ScanConsentRepository(
            new CookieConsentRepository($cookies),
            $this->scanRequest($isScan),
            $this->service(ConsentPolicyFactory::class)
        );
    }
}
