<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\Application\ScanTokens;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Scan\BuildsScanRequests;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScanTokensTest extends WPTestCase
{
    use BuildsContainer;
    use BuildsScanRequests;

    public function testATokenIsValidOnce(): void
    {
        $sut = $this->scanTokens();
        $token = $sut->issue();

        $this->assertTrue($sut->consume($token));
        $this->assertFalse($sut->consume($token));
    }

    public function testAnUnknownTokenIsNotValid(): void
    {
        $this->assertFalse($this->scanTokens()->consume('unknown'));
        $this->assertFalse($this->scanTokens()->consume(''));
    }

    public function testEachTokenIsDifferent(): void
    {
        $sut = $this->scanTokens();

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

        $token = $this->scanTokens()->issue();

        $this->assertSame(['icc_scan_token_' . \hash('sha256', $token) => ScanTokens::LIFETIME], $expirations);
    }
}
