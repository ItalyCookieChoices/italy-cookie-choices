<?php

declare(strict_types=1);

namespace ItalyCookieChoices;

use ItalyStrap\Event\SubscriberInterface;
use ItalyStrap\Event\SubscriberRegisterInterface;

/**
 * Hooks the plugin services into WordPress.
 */
final readonly class Plugin
{
    /**
     * @var list<SubscriberInterface>
     */
    private array $subscribers;

    public function __construct(
        private SubscriberRegisterInterface $subscriberRegister,
        SubscriberInterface ...$subscribers,
    ) {
        $this->subscribers = \array_values($subscribers);
    }

    public function boot(): void
    {
        foreach ($this->subscribers as $subscriber) {
            $this->subscriberRegister->addSubscriber($subscriber);
        }
    }
}
