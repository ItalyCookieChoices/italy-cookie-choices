<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Scan;

use ItalyCookieChoices\Scan\Application\ScanTokens;
use ItalyCookieChoices\Scan\UI\ScanRequest;

/**
 * The request of a page, in scan mode or not, with the tokens of the container: use it with BuildsContainer.
 */
trait BuildsScanRequests
{
    private function scanRequest(bool $isScan): ScanRequest
    {
        $tokens = $this->scanTokens();
        $request = new ScanRequest($tokens, $isScan ? [ScanRequest::QUERY_ARG => $tokens->issue()] : []);
        $request->start();

        return $request;
    }

    private function scanTokens(): ScanTokens
    {
        return $this->service(ScanTokens::class);
    }
}
