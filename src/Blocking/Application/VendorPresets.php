<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Application;

use ItalyCookieChoices\Blocking\Domain\Vendor;
use ItalyCookieChoices\Consent\Domain\Category;

/**
 * The common vendors, blocked by default.
 */
final readonly class VendorPresets
{
    private const NAMES = [
        'google-ads' => 'Google Ads',
        'google-analytics' => 'Google Analytics',
        'google-tag-manager' => 'Google Tag Manager',
        'hotjar' => 'Hotjar',
        'youtube' => 'YouTube',
        'vimeo' => 'Vimeo',
        'google-maps' => 'Google Maps',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'x' => 'X (Twitter)',
        'linkedin' => 'LinkedIn',
        'tiktok' => 'TikTok',
        'spotify' => 'Spotify',
        'soundcloud' => 'SoundCloud',
        'google-adsense' => 'Google AdSense',
        'microsoft-clarity' => 'Microsoft Clarity',
        'microsoft-advertising' => 'Microsoft Advertising',
        'pinterest' => 'Pinterest',
        'snapchat' => 'Snapchat',
        'reddit' => 'Reddit',
        'hubspot' => 'HubSpot',
        'matomo-cloud' => 'Matomo Cloud',
        'google-recaptcha' => 'Google reCAPTCHA',
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
                'google-ads',
                Category::MARKETING,
                ['googleadservices.com', 'googleads.g.doubleclick.net'],
                ['googleadservices.com/pagead/conversion'],
                self::GOOGLE_ADS_IDS
            ),
            new Vendor(
                'google-analytics',
                Category::STATISTICS,
                ['google-analytics.com', 'googletagmanager.com/gtag/js'],
                ['google-analytics.com/analytics.js', 'googletagmanager.com/gtag/js'],
                self::GOOGLE_TAG_IDS
            ),
            new Vendor(
                'google-tag-manager',
                Category::MARKETING,
                ['googletagmanager.com/gtm.js', 'googletagmanager.com/ns.html'],
                ['googletagmanager.com/gtm.js', "'gtm.start'"],
                self::GOOGLE_TAG_MANAGER_IDS
            ),
            new Vendor('hotjar', Category::STATISTICS, ['hotjar.com'], ['static.hotjar.com']),
            new Vendor(
                'youtube',
                Category::MARKETING,
                ['youtube.com', 'youtube-nocookie.com', 'youtu.be'],
                ['youtube.com/iframe_api']
            ),
            new Vendor('vimeo', Category::MARKETING, ['player.vimeo.com'], ['player.vimeo.com/api']),
            new Vendor(
                'google-maps',
                Category::MARKETING,
                ['google.com/maps', 'maps.google.com', 'maps.googleapis.com']
            ),
            new Vendor(
                'facebook',
                Category::MARKETING,
                ['connect.facebook.net', 'facebook.com/plugins', 'facebook.com/tr'],
                ['connect.facebook.net']
            ),
            new Vendor(
                'instagram',
                Category::MARKETING,
                ['instagram.com/embed.js', 'instagram.com/p/'],
                ['instagram.com/embed.js']
            ),
            new Vendor(
                'x',
                Category::MARKETING,
                ['platform.twitter.com', 'platform.x.com'],
                ['platform.twitter.com/widgets.js']
            ),
            new Vendor(
                'linkedin',
                Category::MARKETING,
                ['snap.licdn.com', 'platform.linkedin.com'],
                ['snap.licdn.com']
            ),
            new Vendor(
                'tiktok',
                Category::MARKETING,
                ['tiktok.com/embed', 'analytics.tiktok.com'],
                ['analytics.tiktok.com']
            ),
            new Vendor('spotify', Category::MARKETING, ['open.spotify.com/embed']),
            new Vendor('soundcloud', Category::MARKETING, ['w.soundcloud.com']),
            new Vendor(
                'google-adsense',
                Category::MARKETING,
                ['googlesyndication.com', 'adservice.google.com'],
                ['adsbygoogle']
            ),
            new Vendor('microsoft-clarity', Category::STATISTICS, ['clarity.ms'], ['clarity.ms/tag']),
            new Vendor('microsoft-advertising', Category::MARKETING, ['bat.bing.com'], ['bat.bing.com/bat.js']),
            new Vendor(
                'pinterest',
                Category::MARKETING,
                ['s.pinimg.com/ct', 'ct.pinterest.com', 'assets.pinterest.com'],
                ['s.pinimg.com/ct/core.js']
            ),
            new Vendor(
                'snapchat',
                Category::MARKETING,
                ['sc-static.net', 'tr.snapchat.com'],
                ['sc-static.net/scevent']
            ),
            new Vendor(
                'reddit',
                Category::MARKETING,
                ['redditstatic.com/ads', 'alb.reddit.com'],
                ['redditstatic.com/ads/pixel.js']
            ),
            // The tracking and the chat, not the forms of js.hsforms.net.
            new Vendor(
                'hubspot',
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
            new Vendor('matomo-cloud', Category::STATISTICS, ['matomo.cloud'], ['matomo.cloud']),
            // Turned off by default, the forms that need it do not wait for the consent.
            new Vendor(
                'google-recaptcha',
                Category::MARKETING,
                ['google.com/recaptcha', 'gstatic.com/recaptcha', 'recaptcha.net']
            ),
        ];
    }
}
