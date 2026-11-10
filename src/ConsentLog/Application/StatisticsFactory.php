<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\Application;

use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyCookieChoices\ConsentLog\Domain\ConsentStatistics;

/**
 * The statistics of the consent log, by the optional categories of the current policy.
 */
final readonly class StatisticsFactory
{
    public function __construct(
        private ConsentLog $log,
        private ConsentPolicyFactory $policy,
    ) {
    }

    public function create(): ConsentStatistics
    {
        return new ConsentStatistics(
            $this->log->choicesByMonth(),
            $this->policy->create()->categories()->optionalIds()
        );
    }
}
