<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Application;

/**
 * The names of the settings of the banner and of the consent, in the option of the plugin.
 *
 * Some names come from the 2.x versions, they are kept for the saved options.
 */
final class ConsentSetting
{
    // The texts.
    public const TEXT = 'text';
    public const ACCEPT_BUTTON_TEXT = 'button_text';
    public const REJECT_BUTTON_TEXT = 'reject_button_text';
    public const PREFERENCES_BUTTON_TEXT = 'preferences_button_text';
    public const SAVE_BUTTON_TEXT = 'save_button_text';
    public const CATEGORIES = 'categories';

    // The links.
    public const POLICY_PAGE = 'slug';
    public const POLICY_URL = 'url';
    public const POLICY_TEXT = 'anchor_text';
    public const LEGAL_NOTICE_PAGE = 'legal_notice_page';
    public const LEGAL_NOTICE_URL = 'legal_notice_url';
    public const LEGAL_NOTICE_TEXT = 'legal_notice_text';
    public const LINKS_IN_NEW_TAB = 'target';
    public const LINKS_NOFOLLOW = 'nofollow';

    // The behaviour.
    public const RELOAD = 'reload';
    public const EXCLUDED_PAGES = 'excluded_pages';
    public const CONSENT_DURATION = 'consent_duration';
    public const COOKIE_DOMAIN = 'cookie_domain';
    public const GLOBAL_PRIVACY_CONTROL = 'global_privacy_control';
    public const FLOATING_BUTTON = 'floating_button';
    public const FLOATING_BUTTON_POSITION = 'floating_button_position';
    public const FLOATING_BUTTON_LABEL = 'floating_button_label';

    // The style.
    public const POSITION = 'banner';
    public const LOGO = 'banner_logo';
    public const TEMPLATE = 'js_template';
    public const HTML_MARGIN = 'html_margin';
    public const BACKGROUND_COLOR = 'banner_bg';
    public const TEXT_COLOR = 'banner_text_color';
    public const CUSTOM_CSS = 'customCSS';
    public const BANNER_CLASS = 'bannerStyle';
    public const CONTENT_CLASS = 'contentStyle';
    public const TEXT_CLASS = 'consentText';
    public const LINK_CLASS = 'infoClass';
    public const BUTTON_CLASS = 'closeClass';
}
