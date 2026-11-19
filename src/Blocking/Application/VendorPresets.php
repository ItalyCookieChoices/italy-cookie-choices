<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Application;

use ItalyCookieChoices\Blocking\Domain\VendorId;
use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Consent\Domain\Category;

/**
 * The common vendors, blocked by default.
 */
final readonly class VendorPresets
{
    private const NAMES = [
        VendorId::GOOGLE_ADS => 'Google Ads',
        VendorId::GOOGLE_ANALYTICS => 'Google Analytics',
        VendorId::GOOGLE_TAG_MANAGER => 'Google Tag Manager',
        VendorId::HOTJAR => 'Hotjar',
        VendorId::YOUTUBE => 'YouTube',
        VendorId::VIMEO => 'Vimeo',
        VendorId::GOOGLE_MAPS => 'Google Maps',
        VendorId::FACEBOOK => 'Facebook',
        VendorId::INSTAGRAM => 'Instagram',
        VendorId::X => 'X (Twitter)',
        VendorId::LINKEDIN => 'LinkedIn',
        VendorId::TIKTOK => 'TikTok',
        VendorId::SPOTIFY => 'Spotify',
        VendorId::SOUNDCLOUD => 'SoundCloud',
        VendorId::GOOGLE_ADSENSE => 'Google AdSense',
        VendorId::MICROSOFT_CLARITY => 'Microsoft Clarity',
        VendorId::MICROSOFT_ADVERTISING => 'Microsoft Advertising',
        VendorId::PINTEREST => 'Pinterest',
        VendorId::SNAPCHAT => 'Snapchat',
        VendorId::REDDIT => 'Reddit',
        VendorId::HUBSPOT => 'HubSpot',
        VendorId::MATOMO_CLOUD => 'Matomo Cloud',
        VendorId::GOOGLE_RECAPTCHA => 'Google reCAPTCHA',
    ];

    /**
     * The ids of the Google tags loaded by gtag.js, like G-ABC123: they match on any host,
     * so the tags served from the site domain by the Google tag gateway are blocked too.
     */
    private const GOOGLE_TAG_IDS = ['G-', 'GT-'];

    /**
     * The ids of the Google Ads and Floodlight tags, loaded by gtag.js too.
     */
    private const GOOGLE_ADS_IDS = ['AW-', 'DC-'];

    private const GOOGLE_TAG_MANAGER_IDS = ['GTM-'];

    /**
     * The name shown to the site owner, the id when the vendor is not a preset.
     */
    public function name(string $id): string
    {
        return self::NAMES[$id] ?? $id;
    }

    /**
     * Google Ads comes first: its tags load from the address of gtag.js, like Google Analytics.
     *
     * @return list<Vendor>
     */
    public function all(): array
    {
        return [
            new Vendor(
                VendorId::GOOGLE_ADS,
                Category::MARKETING,
                ['googleadservices.com', 'googleads.g.doubleclick.net'],
                ['googleadservices.com/pagead/conversion'],
                self::GOOGLE_ADS_IDS
            ),
            new Vendor(
                VendorId::GOOGLE_ANALYTICS,
                Category::STATISTICS,
                ['google-analytics.com', 'googletagmanager.com/gtag/js'],
                ['google-analytics.com/analytics.js', 'googletagmanager.com/gtag/js'],
                self::GOOGLE_TAG_IDS
            ),
            new Vendor(
                VendorId::GOOGLE_TAG_MANAGER,
                Category::MARKETING,
                ['googletagmanager.com/gtm.js', 'googletagmanager.com/ns.html'],
                ['googletagmanager.com/gtm.js', "'gtm.start'"],
                self::GOOGLE_TAG_MANAGER_IDS
            ),
            new Vendor(VendorId::HOTJAR, Category::STATISTICS, ['hotjar.com'], ['static.hotjar.com']),
            new Vendor(
                VendorId::YOUTUBE,
                Category::MARKETING,
                ['youtube.com', 'youtube-nocookie.com', 'youtu.be'],
                ['youtube.com/iframe_api']
            ),
            new Vendor(VendorId::VIMEO, Category::MARKETING, ['player.vimeo.com'], ['player.vimeo.com/api']),
            new Vendor(
                VendorId::GOOGLE_MAPS,
                Category::MARKETING,
                ['google.com/maps', 'maps.google.com', 'maps.googleapis.com']
            ),
            new Vendor(
                VendorId::FACEBOOK,
                Category::MARKETING,
                ['connect.facebook.net', 'facebook.com/plugins', 'facebook.com/tr'],
                ['connect.facebook.net']
            ),
            new Vendor(
                VendorId::INSTAGRAM,
                Category::MARKETING,
                ['instagram.com/embed.js', 'instagram.com/p/'],
                ['instagram.com/embed.js']
            ),
            new Vendor(
                VendorId::X,
                Category::MARKETING,
                ['platform.twitter.com', 'platform.x.com'],
                ['platform.twitter.com/widgets.js']
            ),
            new Vendor(
                VendorId::LINKEDIN,
                Category::MARKETING,
                ['snap.licdn.com', 'platform.linkedin.com'],
                ['snap.licdn.com']
            ),
            new Vendor(
                VendorId::TIKTOK,
                Category::MARKETING,
                ['tiktok.com/embed', 'analytics.tiktok.com'],
                ['analytics.tiktok.com']
            ),
            new Vendor(VendorId::SPOTIFY, Category::MARKETING, ['open.spotify.com/embed']),
            new Vendor(VendorId::SOUNDCLOUD, Category::MARKETING, ['w.soundcloud.com']),
            new Vendor(
                VendorId::GOOGLE_ADSENSE,
                Category::MARKETING,
                ['googlesyndication.com', 'adservice.google.com'],
                ['adsbygoogle']
            ),
            new Vendor(VendorId::MICROSOFT_CLARITY, Category::STATISTICS, ['clarity.ms'], ['clarity.ms/tag']),
            new Vendor(VendorId::MICROSOFT_ADVERTISING, Category::MARKETING, ['bat.bing.com'], ['bat.bing.com/bat.js']),
            new Vendor(
                VendorId::PINTEREST,
                Category::MARKETING,
                ['s.pinimg.com/ct', 'ct.pinterest.com', 'assets.pinterest.com'],
                ['s.pinimg.com/ct/core.js']
            ),
            new Vendor(
                VendorId::SNAPCHAT,
                Category::MARKETING,
                ['sc-static.net', 'tr.snapchat.com'],
                ['sc-static.net/scevent']
            ),
            new Vendor(
                VendorId::REDDIT,
                Category::MARKETING,
                ['redditstatic.com/ads', 'alb.reddit.com'],
                ['redditstatic.com/ads/pixel.js']
            ),
            // The tracking and the chat, not the forms of js.hsforms.net.
            new Vendor(
                VendorId::HUBSPOT,
                Category::MARKETING,
                [
                    'js.hs-scripts.com',
                    'js.hs-analytics.net',
                    'js.hs-banner.com',
                    'js.hsadspixel.net',
                    'js.hscollectedforms.net',
                    'js.usemessages.com',
                ],
                ['js.hs-scripts.com']
            ),
            // Only the cloud: Matomo on the site can be set up without cookies.
            new Vendor(VendorId::MATOMO_CLOUD, Category::STATISTICS, ['matomo.cloud'], ['matomo.cloud']),
            // Turned off by default, the forms that need it do not wait for the consent.
            new Vendor(
                VendorId::GOOGLE_RECAPTCHA,
                Category::MARKETING,
                ['google.com/recaptcha', 'gstatic.com/recaptcha', 'recaptcha.net']
            ),
        ];
    }
}
