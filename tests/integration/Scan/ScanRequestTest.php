<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\Application\ScanTokens;
use ItalyCookieChoices\Scan\UI\ScanRequest;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScanRequestTest extends WPTestCase
{
    public function testItStartsAfterTheRedirects(): void
    {
        $this->assertSame(
            [
                'template_redirect' => [
                    SubscriberInterface::CALLBACK => 'start',
                    SubscriberInterface::PRIORITY => \PHP_INT_MAX - 1,
                ],
            ],
            \iterator_to_array((new ScanRequest(new ScanTokens(), []))->getSubscribedEvents())
        );
    }

    public function testAPageWithAValidTokenIsScanned(): void
    {
        $tokens = new ScanTokens();
        $sut = new ScanRequest($tokens, [ScanRequest::QUERY_ARG => $tokens->issue()]);

        $sut->start();

        $this->assertTrue($sut->isScan());
    }

    public function testThePageIsNotScannedBeforeItStarts(): void
    {
        $tokens = new ScanTokens();

        $this->assertFalse((new ScanRequest($tokens, [ScanRequest::QUERY_ARG => $tokens->issue()]))->isScan());
    }

    public function testATokenScansOnePage(): void
    {
        $tokens = new ScanTokens();
        $query = [ScanRequest::QUERY_ARG => $tokens->issue()];
        (new ScanRequest($tokens, $query))->start();
        $second = new ScanRequest($tokens, $query);

        $second->start();

        $this->assertFalse($second->isScan());
    }

    public function testAPageWithoutAValidTokenIsNotScanned(): void
    {
        foreach ([[], [ScanRequest::QUERY_ARG => 'unknown'], [ScanRequest::QUERY_ARG => ['array']]] as $query) {
            $sut = new ScanRequest(new ScanTokens(), $query);

            $sut->start();

            $this->assertFalse($sut->isScan());
        }
    }
}
