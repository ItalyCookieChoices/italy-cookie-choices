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
        $optional = [];
        foreach ($this->policy->create()->categories() as $category) {
            if ($category->isRequired()) {
                continue;
            }

            $optional[] = $category->id();
        }

        return new ConsentStatistics($this->log->choicesByMonth(), $optional);
    }
}
