<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support;

use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\StringTranslations;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalDispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;

/**
 * The saved settings read with the WordPress hooks, as the container builds them.
 */
trait BuildsOptions
{
    private function options(): Options
    {
        return new Options(new StringTranslations(
            new GlobalDispatcher(),
            new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState())
        ));
    }
}
