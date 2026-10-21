<?php

/**
 * The composition root: the container autowires the plugin services and hooks them into WordPress.
 */

declare(strict_types=1);

namespace ItalyCookieChoices;

use ItalyStrap\Empress\ContainerBuilder;
use ItalyStrap\Event\Module as EventModule;
use ItalyStrap\Event\SubscribersConfigExtension;

require __DIR__ . '/legacy/bootstrap.php';

(new ContainerBuilder())
    ->addModule(new EventModule())
    ->addModule(new Module(ITALY_COOKIE_CHOICES_PLUGIN_PATH, \dirname(ITALY_COOKIE_CHOICES_BASENAME) . '/lang'))
    ->extend(SubscribersConfigExtension::class)
    ->build();
