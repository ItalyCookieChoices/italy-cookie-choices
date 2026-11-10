<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\Infrastructure;

use ItalyCookieChoices\Consent\Application\ConsentCookieDomain;
use ItalyCookieChoices\Consent\Infrastructure\CookieConsentStorage;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieConsentStorageTest extends WPTestCase
{
    use BuildsContainer;

    private const JSON = '{"id":"3f2504e0-4f89-41d3-9a0c-0305e82c3301","categories":["necessary"],"version":"1",'
        . '"date":"2026-11-01T10:00:00.000Z"}';

    /**
     * @var list<array{string, string, array<string, mixed>}>
     */
    private array $sent = [];

    public function testItSendsTheStoredCookieBackUntilTheDate(): void
    {
        $expiresAt = new \DateTimeImmutable('2027-05-02T10:00:00+00:00');

        $this->makeSut(['icc_consent' => \wp_slash(self::JSON)])->renew($expiresAt);

        $this->assertSame(
            [[
                'icc_consent',
                \rawurlencode(self::JSON),
                [
                    'expires' => $expiresAt->getTimestamp(),
                    'path' => '/',
                    'secure' => false,
                    'httponly' => false,
                    'samesite' => 'Lax',
                ],
            ]],
            $this->sent
        );
    }

    public function testTheValueIsEncodedAsTheBrowserDecodesIt(): void
    {
        $json = '{"version":"a b+c"}';

        $this->makeSut(['icc_consent' => $json])->renew(new \DateTimeImmutable('+1 day'));

        $this->assertSame('%7B%22version%22%3A%22a%20b%2Bc%22%7D', $this->sent[0][1] ?? null);
    }

    public function testOnHttpsTheCookieIsSecure(): void
    {
        $_SERVER['HTTPS'] = 'on';

        try {
            $this->makeSut(['icc_consent' => self::JSON])->renew(new \DateTimeImmutable('+1 day'));
        } finally {
            unset($_SERVER['HTTPS']);
        }

        $this->assertTrue($this->sent[0][2]['secure'] ?? null);
    }

    public function testTheCookieIsSentForTheCookieDomain(): void
    {
        $this->makeSut(['icc_consent' => self::JSON], 'example.com')->renew(new \DateTimeImmutable('+1 day'));

        $this->assertSame('example.com', $this->sent[0][2]['domain'] ?? null);
    }

    public function testWithoutTheCookieNothingIsSent(): void
    {
        $this->makeSut([])->renew(new \DateTimeImmutable('+1 day'));
        $this->makeSut(['icc_consent' => ['a']])->renew(new \DateTimeImmutable('+1 day'));

        $this->assertSame([], $this->sent);
    }

    /**
     * @param array<array-key, mixed> $cookies
     */
    private function makeSut(array $cookies, string $domain = ''): CookieConsentStorage
    {
        \add_filter('home_url', static fn(): string => 'https://www.example.com');
        \update_option(Schema::OPTION, ['active' => '1', 'cookie_domain' => $domain]);

        return new CookieConsentStorage(
            $cookies,
            $this->service(ConsentCookieDomain::class),
            function (string $name, string $value, array $options): bool {
                /** @var array<string, mixed> $options */
                $this->sent[] = [$name, $value, $options];

                return true;
            }
        );
    }
}
