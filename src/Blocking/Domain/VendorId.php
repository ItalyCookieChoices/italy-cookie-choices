<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Domain;

/**
 * The ids of the vendors the plugin knows: the presets, and the Google tag gateway built from its path.
 * They are saved in the option, like in the list of the presets turned off.
 */
final class VendorId
{
    public const GOOGLE_ADS = 'google-ads';
    public const GOOGLE_ANALYTICS = 'google-analytics';
    public const GOOGLE_TAG_MANAGER = 'google-tag-manager';
    public const HOTJAR = 'hotjar';
    public const YOUTUBE = 'youtube';
    public const VIMEO = 'vimeo';
    public const GOOGLE_MAPS = 'google-maps';
    public const FACEBOOK = 'facebook';
    public const INSTAGRAM = 'instagram';
    public const X = 'x';
    public const LINKEDIN = 'linkedin';
    public const TIKTOK = 'tiktok';
    public const SPOTIFY = 'spotify';
    public const SOUNDCLOUD = 'soundcloud';
    public const GOOGLE_ADSENSE = 'google-adsense';
    public const MICROSOFT_CLARITY = 'microsoft-clarity';
    public const MICROSOFT_ADVERTISING = 'microsoft-advertising';
    public const PINTEREST = 'pinterest';
    public const SNAPCHAT = 'snapchat';
    public const REDDIT = 'reddit';
    public const HUBSPOT = 'hubspot';
    public const MATOMO_CLOUD = 'matomo-cloud';
    public const GOOGLE_RECAPTCHA = 'google-recaptcha';
    public const CALENDLY = 'calendly';
    public const MAILCHIMP = 'mailchimp';
    public const DISQUS = 'disqus';
    public const DAILYMOTION = 'dailymotion';
    public const TWITCH = 'twitch';
    public const AMAZON_ADS = 'amazon-ads';
    public const GOOGLE_TAG_GATEWAY = 'google-tag-gateway';
}
