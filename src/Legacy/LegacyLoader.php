<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Legacy;

use ItalyStrap\Event\SubscriberInterface;

/**
 * Loads the legacy front end code, until each feature moves to src.
 */
final readonly class LegacyLoader implements SubscriberInterface
{
    /**
     * @param string $legacyPath The legacy folder, with the trailing slash.
     */
    public function __construct(
        private string $legacyPath,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'plugins_loaded' => [
            self::CALLBACK => 'load',
            self::PRIORITY => 11,
        ];
    }

    public function load(): void
    {
        require $this->legacyPath . 'init.php';
    }
}
