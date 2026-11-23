<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Scan;

use ItalyCookieChoices\Scan\Application\ScanTokens;
use ItalyCookieChoices\Scan\UI\ScanRequest;

/**
 * The request of a page, in scan mode or not.
 */
trait BuildsScanRequests
{
    private function scanRequest(bool $isScan): ScanRequest
    {
        $tokens = new ScanTokens();
        $request = new ScanRequest($tokens, $isScan ? [ScanRequest::QUERY_ARG => $tokens->issue()] : []);
        $request->start();

        return $request;
    }
}
