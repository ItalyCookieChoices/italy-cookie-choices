<?php

declare(strict_types=1);

namespace ItalyCookieChoices\WordPress;

/**
 * The capabilities of WordPress the plugin checks in more than one class.
 */
final class Capability
{
    /** The administrators of a site, who manage the settings of the plugin. */
    public const MANAGE_OPTIONS = 'manage_options';
}
