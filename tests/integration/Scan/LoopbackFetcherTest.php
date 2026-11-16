<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\Infrastructure\LoopbackFetcher;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class LoopbackFetcherTest extends WPTestCase
{
    /** @var list<array{url: string, args: array<array-key, mixed>}> */
    private array $requests = [];

    public function testItReadsThePageWithoutCookiesAndWithoutThePageCache(): void
    {
        $this->respond(['response' => ['code' => 200, 'message' => 'OK'], 'body' => '<html></html>']);

        $html = (new LoopbackFetcher())->fetch('https://example.com/about/');

        $this->assertSame('<html></html>', $html);
        $this->assertStringStartsWith('https://example.com/about/?icc-scan=', $this->requests[0]['url'] ?? '');
        $this->assertSame([], $this->requests[0]['args']['cookies'] ?? []);
    }

    public function testItTellsTheStatusOfAPageThatFails(): void
    {
        $this->respond(['response' => ['code' => 503, 'message' => 'Unavailable'], 'body' => '']);

        $this->expectExceptionMessage('The page answered with the status 503.');

        (new LoopbackFetcher())->fetch('https://example.com/');
    }

    public function testItTellsWhyTheSiteDidNotAnswer(): void
    {
        $this->respond(new \WP_Error('http_request_failed', 'cURL error 7: Failed to connect'));

        $this->expectExceptionMessage('The site did not answer to the scan: cURL error 7: Failed to connect');

        (new LoopbackFetcher())->fetch('https://example.com/');
    }

    /**
     * @param array<string, mixed>|\WP_Error $response
     */
    private function respond(array|\WP_Error $response): void
    {
        \add_filter(
            'pre_http_request',
            function (mixed $preempt, array $args, string $url) use ($response): array|\WP_Error {
                $this->requests[] = ['url' => $url, 'args' => $args];

                return $response;
            },
            10,
            3
        );
    }
}
