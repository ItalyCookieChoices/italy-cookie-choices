<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\UI\ScanRequest;
use ItalyStrap\Event\SubscriberInterface;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Scan\BuildsScanRequests;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScanRequestTest extends WPTestCase
{
    use BuildsContainer;
    use BuildsScanRequests;

    public function testItStartsAfterTheRedirects(): void
    {
        $this->assertSame(
            [
                'template_redirect' => [
                    SubscriberInterface::CALLBACK => 'start',
                    SubscriberInterface::PRIORITY => \PHP_INT_MAX - 1,
                ],
            ],
            \iterator_to_array((new ScanRequest($this->scanTokens(), []))->getSubscribedEvents())
        );
    }

    public function testAPageWithAValidTokenIsScanned(): void
    {
        $tokens = $this->scanTokens();
        $sut = new ScanRequest($tokens, [ScanRequest::QUERY_ARG => $tokens->issue()]);

        $sut->start();

        $this->assertTrue($sut->isScan());
    }

    public function testThePageIsNotScannedBeforeItStarts(): void
    {
        $tokens = $this->scanTokens();

        $this->assertFalse((new ScanRequest($tokens, [ScanRequest::QUERY_ARG => $tokens->issue()]))->isScan());
    }

    public function testATokenScansOnePage(): void
    {
        $tokens = $this->scanTokens();
        $query = [ScanRequest::QUERY_ARG => $tokens->issue()];
        (new ScanRequest($tokens, $query))->start();
        $second = new ScanRequest($tokens, $query);

        $second->start();

        $this->assertFalse($second->isScan());
    }

    public function testAPageWithoutAValidTokenIsNotScanned(): void
    {
        foreach ([[], [ScanRequest::QUERY_ARG => 'unknown'], [ScanRequest::QUERY_ARG => ['array']]] as $query) {
            $sut = new ScanRequest($this->scanTokens(), $query);

            $sut->start();

            $this->assertFalse($sut->isScan());
        }
    }
}
