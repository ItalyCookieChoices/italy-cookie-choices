<?php

declare(strict_types=1);

namespace ItalyCookieChoices\WordPress;

/**
 * The hooks of WordPress the plugin listens to in more than one class.
 *
 * The hooks of the scripts and of the styles are in the EventName class of italystrap/asset.
 */
final class Hook
{
    public const INIT = 'init';
    public const PLUGINS_LOADED = 'plugins_loaded';
    public const TEMPLATE_REDIRECT = 'template_redirect';
    public const HEAD = 'wp_head';
    public const FOOTER = 'wp_footer';
    public const ADMIN_INIT = 'admin_init';
    public const ADMIN_NOTICES = 'admin_notices';
    /** The prefix of the action of a form sent to admin-post.php, followed by the name of the action. */
    public const ADMIN_POST = 'admin_post_';
    public const REST_API_INIT = 'rest_api_init';
    public const ABILITIES_API_INIT = 'wp_abilities_api_init';
}
