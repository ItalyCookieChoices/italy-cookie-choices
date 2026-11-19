<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

/**
 * The names of the settings of the plugin itself, in the option of Schema::OPTION.
 */
final class PluginSetting
{
    /** Turns the plugin on: without it nothing is printed and nothing is blocked. */
    public const ACTIVE = 'active';
    /** The name of the cookie the 2.x versions wrote on accept. */
    public const LEGACY_COOKIE_NAME = 'cookie_name';
    /** The value of the cookie the 2.x versions wrote on accept. */
    public const LEGACY_COOKIE_VALUE = 'cookie_value';
}
