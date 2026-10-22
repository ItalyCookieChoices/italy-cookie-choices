<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Application;

use ItalyCookieChoices\Consent\Domain\Categories;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\Domain\ConsentDuration;
use ItalyCookieChoices\Consent\Domain\ConsentPolicy;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Builds the consent policy from the defaults and the listeners of BuildingConsentPolicy.
 *
 * It is built when it is needed, after the themes have added their listeners.
 */
final readonly class ConsentPolicyFactory
{
    public const DEFAULT_VERSION = '1';

    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function create(): ConsentPolicy
    {
        $event = new BuildingConsentPolicy(
            new Categories(
                new Category(Category::NECESSARY, true),
                new Category(Category::PREFERENCES),
                new Category(Category::STATISTICS),
                new Category(Category::MARKETING)
            ),
            new PolicyVersion(self::DEFAULT_VERSION),
            new ConsentDuration()
        );
        $this->dispatcher->dispatch($event);

        return $event->policy();
    }
}
