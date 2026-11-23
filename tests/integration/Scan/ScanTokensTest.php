<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\Application\ScanTokens;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScanTokensTest extends WPTestCase
{
    public function testATokenIsValidOnce(): void
    {
        $sut = new ScanTokens();
        $token = $sut->issue();

        $this->assertTrue($sut->consume($token));
        $this->assertFalse($sut->consume($token));
    }

    public function testAnUnknownTokenIsNotValid(): void
    {
        $this->assertFalse((new ScanTokens())->consume('unknown'));
        $this->assertFalse((new ScanTokens())->consume(''));
    }

    public function testEachTokenIsDifferent(): void
    {
        $sut = new ScanTokens();

        $this->assertNotSame($sut->issue(), $sut->issue());
    }

    public function testATokenExpiresAfterAFewMinutes(): void
    {
        $expirations = [];
        \add_action(
            'set_transient',
            static function (string $transient, mixed $value, int $expiration) use (&$expirations): void {
                $expirations[$transient] = $expiration;
            },
            10,
            3
        );

        $token = (new ScanTokens())->issue();

        $this->assertSame(['icc_scan_token_' . \hash('sha256', $token) => ScanTokens::LIFETIME], $expirations);
    }
}
