<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\Infrastructure;

use ItalyCookieChoices\Consent\Infrastructure\CookieConsentRepository;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieConsentRepositoryTest extends WPTestCase
{
    public function testItReadsTheConsentWrittenByTheBrowser(): void
    {
        $sut = new CookieConsentRepository([
            'icc_consent' => \wp_slash(
                '{"categories":["necessary","statistics"],"version":"2","date":"2026-10-22T10:00:00.123Z"}'
            ),
        ]);

        $consent = $sut->current();

        $this->assertNotNull($consent);
        $this->assertSame(['necessary', 'statistics'], $consent->categories());
        $this->assertSame('2', $consent->version()->toString());
        $this->assertEquals(new \DateTimeImmutable('2026-10-22T10:00:00.123+00:00'), $consent->givenAt());
    }

    public function testThereIsNoConsentWithoutTheCookie(): void
    {
        $this->assertNull((new CookieConsentRepository([]))->current());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public function invalidCookies(): iterable
    {
        yield 'not a string' => [['a']];
        yield 'not JSON' => ['consent'];
        yield 'JSON scalar' => ['"consent"'];
        yield 'missing version' => ['{"categories":[],"date":"2026-10-22T10:00:00.123Z"}'];
        yield 'empty version' => ['{"categories":[],"version":" ","date":"2026-10-22T10:00:00.123Z"}'];
        yield 'numeric version' => ['{"categories":[],"version":1,"date":"2026-10-22T10:00:00.123Z"}'];
        yield 'missing categories' => ['{"version":"1","date":"2026-10-22T10:00:00.123Z"}'];
        yield 'categories object' => [
            '{"categories":{"a":"necessary"},"version":"1","date":"2026-10-22T10:00:00.123Z"}',
        ];
        yield 'category not a string' => ['{"categories":[1],"version":"1","date":"2026-10-22T10:00:00.123Z"}'];
        yield 'missing date' => ['{"categories":[],"version":"1"}'];
        yield 'invalid date' => ['{"categories":[],"version":"1","date":"yesterday"}'];
    }

    /**
     * @dataProvider invalidCookies
     */
    public function testAnInvalidCookieIsNoConsent(mixed $value): void
    {
        $this->assertNull((new CookieConsentRepository(['icc_consent' => $value]))->current());
    }
}
