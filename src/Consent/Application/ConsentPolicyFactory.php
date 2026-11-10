<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Application;

use ItalyCookieChoices\Consent\Domain\Categories;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\Domain\ConsentDuration;
use ItalyCookieChoices\Consent\Domain\ConsentPolicy;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\Settings\Options;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Builds the consent policy from the defaults, the saved duration and the listeners of BuildingConsentPolicy.
 *
 * It is built when it is needed, after the themes have added their listeners.
 */
final readonly class ConsentPolicyFactory
{
    public const DEFAULT_VERSION = '1';

    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private Options $options,
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
            $this->duration()
        );
        $this->dispatcher->dispatch($event);

        return $event->policy();
    }

    /**
     * The saved number of days, the default one when it is not a positive number.
     */
    private function duration(): ConsentDuration
    {
        $days = (int) $this->options->text('consent_duration');

        return $days > 0 ? new ConsentDuration($days) : new ConsentDuration();
    }
}
