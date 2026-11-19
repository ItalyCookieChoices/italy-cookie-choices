<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Application;

/**
 * The names of the settings of the blocking, in the option of the plugin.
 */
final class BlockingSetting
{
    /** The presets of the vendors turned off. */
    public const DISABLED_VENDORS = 'disabled_vendors';
    public const CUSTOM_VENDORS = 'custom_vendors';
    /** The addresses and the scripts never blocked. */
    public const ALLOWED_PATTERNS = 'allowed_patterns';
    /** The compatibility mode: the vendors are blocked in the whole page. */
    public const WHOLE_PAGE = 'all_block';
    /** The visitors choose the single vendors inside the categories. */
    public const VENDOR_CONSENT = 'vendor_consent';
    public const GOOGLE_TAG_GATEWAY_PATH = 'google_tag_gateway_path';
    public const CUSTOM_SCRIPTS = 'custom_scripts';
    public const PLACEHOLDER_TEXT = 'content_message_text';
    public const PLACEHOLDER_BUTTON_TEXT = 'content_message_button_text';
}
