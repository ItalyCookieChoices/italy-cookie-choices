<?php

/**
 * The composition root: builds the plugin services and hooks them into WordPress.
 *
 * Loaded only when the platform requirements are met, so it can use the PHP 8.2 syntax.
 */

declare(strict_types=1);

namespace ItalyCookieChoices;

use ItalyCookieChoices\I18n\TextDomain;
use ItalyCookieChoices\Legacy\LegacyLoader;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\SubscriberRegister;

require __DIR__ . '/legacy/bootstrap.php';

(new Plugin(
    new SubscriberRegister(new GlobalOrderedListenerProvider()),
    new TextDomain(\dirname(ITALY_COOKIE_CHOICES_BASENAME) . '/lang'),
    new LegacyLoader(ITALY_COOKIE_CHOICES_PLUGIN_PATH),
))->boot();
