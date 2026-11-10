<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\Application;

use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Application\RenewConsent;
use ItalyCookieChoices\Consent\Domain\ConsentStorage;
use ItalyCookieChoices\Consent\Infrastructure\CookieConsentRepository;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class RenewConsentTest extends WPTestCase
{
    use BuildsOptions;

    private const GIVEN_AT = '2026-11-01T10:00:00.000Z';

    public function testAValidConsentIsKeptUntilItExpires(): void
    {
        $storage = $this->storage();

        $renewed = $this->makeSut($this->cookies('1', self::GIVEN_AT), $storage)($this->now());

        $this->assertTrue($renewed);
        $this->assertSame(['2027-05-02T10:00:00+00:00'], $storage->renewals);
    }

    public function testWithoutAConsentNothingIsRenewed(): void
    {
        $storage = $this->storage();

        $this->assertFalse($this->makeSut([], $storage)($this->now()));
        $this->assertSame([], $storage->renewals);
    }

    public function testAnExpiredConsentIsNotRenewed(): void
    {
        $storage = $this->storage();

        $renewed = $this->makeSut($this->cookies('1', '2026-01-01T10:00:00.000Z'), $storage)($this->now());

        $this->assertFalse($renewed);
        $this->assertSame([], $storage->renewals);
    }

    public function testAConsentForAnotherVersionIsNotRenewed(): void
    {
        $storage = $this->storage();

        $renewed = $this->makeSut($this->cookies('2', self::GIVEN_AT), $storage)($this->now());

        $this->assertFalse($renewed);
        $this->assertSame([], $storage->renewals);
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-11-04T10:00:00+00:00');
    }

    /**
     * @return array<string, string>
     */
    private function cookies(string $version, string $date): array
    {
        return [
            'icc_consent' => \wp_slash((string) \wp_json_encode([
                'id' => '3f2504e0-4f89-41d3-9a0c-0305e82c3301',
                'categories' => ['necessary', 'statistics'],
                'version' => $version,
                'date' => $date,
            ])),
        ];
    }

    /**
     * @return ConsentStorage&object{renewals: list<string>}
     */
    private function storage(): ConsentStorage
    {
        return new class implements ConsentStorage {
            /**
             * @var list<string>
             */
            public array $renewals = [];

            public function renew(\DateTimeImmutable $expiresAt): void
            {
                $this->renewals[] = $expiresAt->format(\DateTimeInterface::ATOM);
            }
        };
    }

    /**
     * @param array<string, string> $cookies
     */
    private function makeSut(array $cookies, ConsentStorage $storage): RenewConsent
    {
        return new RenewConsent(
            new ConsentPolicyFactory(
                new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState()),
                $this->options()
            ),
            new CookieConsentRepository($cookies),
            $storage
        );
    }
}
