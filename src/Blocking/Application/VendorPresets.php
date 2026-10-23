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
    ];

    /**
     * The name shown to the site owner, the id when the vendor is not a preset.
     */
    public function name(string $id): string
    {
        return self::NAMES[$id] ?? $id;
    }

    /**
     * @return list<Vendor>
     */
    public function all(): array
    {
        return [
            new Vendor(
                'google-analytics',
                Category::STATISTICS,
                ['google-analytics.com', 'googletagmanager.com/gtag/js'],
                ['google-analytics.com/analytics.js', 'googletagmanager.com/gtag/js']
            ),
            new Vendor(
                'google-tag-manager',
                Category::MARKETING,
                ['googletagmanager.com/gtm.js', 'googletagmanager.com/ns.html'],
                ['googletagmanager.com/gtm.js']
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
        ];
    }
}
