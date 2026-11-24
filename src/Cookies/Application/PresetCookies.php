<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cookies\Application;

use ItalyCookieChoices\Blocking\Domain\VendorId;

/**
 * The cookies the common vendors set, from their documentation or, when it lacks the durations, their scripts
 * and cookiedatabase.org: a vendor can change them.
 */
final readonly class PresetCookies
{
    public function __construct(
        private CookieDurations $durations,
    ) {
    }

    /**
     * @return list<array{name: string, duration: string, purpose: string}> None for a vendor without cookies.
     */
    public function of(string $vendor): array
    {
        return $this->all()[$vendor] ?? [];
    }

    /**
     * @return array{name: string, duration: string, purpose: string}
     */
    private function cookie(string $name, string $duration, string $purpose): array
    {
        return ['name' => $name, 'duration' => $duration, 'purpose' => $purpose];
    }

    /**
     * Built on each call, so the texts are translated in the language of the request.
     * Google Tag Manager sets no cookie of its own, the embeds of Instagram, X and SoundCloud set them
     * on their own domains and do not document them.
     *
     * @return array<string, list<array{name: string, duration: string, purpose: string}>>
     */
    private function all(): array
    {
        $d = $this->durations;

        return [
            VendorId::GOOGLE_ADS => [
                $this->cookie(
                    '_gcl_au',
                    $d->days(90),
                    \__('Stores the conversions of the Google ads.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '_gcl_aw',
                    $d->days(90),
                    \__('Stores the click on a Google ad, to attribute the conversions.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    'IDE',
                    $d->months(13),
                    \__('Shows and personalizes the Google ads on other sites.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    'test_cookie',
                    $d->minutes(15),
                    \__('Checks if the browser accepts cookies.', 'italy-cookie-choices')
                ),
            ],
            VendorId::GOOGLE_ANALYTICS => [
                $this->cookie(
                    '_ga',
                    $d->years(2),
                    \__('Tells the visitors apart, for the statistics of the visits.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '_ga_*',
                    $d->years(2),
                    \__('Keeps the state of the session, for the statistics of the visits.', 'italy-cookie-choices')
                ),
            ],
            VendorId::HOTJAR => [
                $this->cookie(
                    '_hjSessionUser_*',
                    $d->years(1),
                    \__('Keeps the id of the visitor for the site.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '_hjSession_*',
                    $d->minutes(30),
                    \__('Keeps the data of the current session.', 'italy-cookie-choices')
                ),
            ],
            VendorId::YOUTUBE => [
                $this->cookie(
                    'YSC',
                    $d->session(),
                    \__('Checks that the requests of a session come from the same visitor.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '__Secure-YNID',
                    $d->months(6),
                    \__('Measures the views and suggests videos and ads.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    'PREF',
                    $d->months(8),
                    \__('Stores the preferences of the video player.', 'italy-cookie-choices')
                ),
            ],
            VendorId::VIMEO => [
                $this->cookie(
                    'vuid',
                    $d->years(2),
                    \__('Measures the views of the videos for their owner.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    'player',
                    $d->years(1),
                    \__('Stores the preferences of the video player.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '__cf_bm',
                    $d->minutes(30),
                    \__('Tells the bots apart from the visitors.', 'italy-cookie-choices')
                ),
            ],
            VendorId::GOOGLE_MAPS => [
                $this->cookie(
                    'NID',
                    $d->months(6),
                    \__(
                        'Stores the preferences and shows ads to the visitors not signed in to Google.',
                        'italy-cookie-choices'
                    )
                ),
            ],
            VendorId::FACEBOOK => [
                $this->cookie(
                    '_fbp',
                    $d->months(3),
                    \__('Tells the visitors apart, to measure and show the Meta ads.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '_fbc',
                    $d->days(90),
                    \__('Stores the click on a Meta ad.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    'fr',
                    $d->months(3),
                    \__('Shows and measures the Meta ads.', 'italy-cookie-choices')
                ),
            ],
            VendorId::LINKEDIN => [
                $this->cookie(
                    'li_fat_id',
                    $d->days(30),
                    \__('Stores the LinkedIn member id, for the conversions and the ads.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    'bcookie',
                    $d->years(1),
                    \__('Identifies the browser, to detect abuse.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    'lidc',
                    $d->days(1),
                    \__('Chooses the data center.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    'UserMatchHistory',
                    $d->days(30),
                    \__('Syncs the id of the LinkedIn ads.', 'italy-cookie-choices')
                ),
            ],
            VendorId::TIKTOK => [
                $this->cookie(
                    '_ttp',
                    $d->months(13),
                    \__('Measures the ad campaigns and personalizes the ads.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    'ttclid',
                    $d->months(13),
                    \__('Stores the click on a TikTok ad, for the conversions.', 'italy-cookie-choices')
                ),
            ],
            VendorId::SPOTIFY => [
                $this->cookie(
                    'sp_t',
                    $d->years(1),
                    \__('Recognizes the browser across the pages.', 'italy-cookie-choices')
                ),
            ],
            VendorId::GOOGLE_ADSENSE => [
                $this->cookie(
                    '__gads',
                    $d->months(13),
                    \__('Lets the site show the Google ads.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '__gpi',
                    $d->months(13),
                    \__('Shows the Google ads and protects them.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '__eoi',
                    $d->months(6),
                    \__('Protects the ads from fraud.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    'IDE',
                    $d->months(13),
                    \__('Shows and personalizes the Google ads on other sites.', 'italy-cookie-choices')
                ),
            ],
            VendorId::MICROSOFT_CLARITY => [
                $this->cookie(
                    '_clck',
                    $d->years(1),
                    \__('Keeps the id of the visitor for Clarity.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '_clsk',
                    $d->days(1),
                    \__('Joins the pages viewed into one session recording.', 'italy-cookie-choices')
                ),
            ],
            VendorId::MICROSOFT_ADVERTISING => [
                $this->cookie(
                    '_uetsid',
                    $d->days(1),
                    \__('Keeps the session, for the ad conversions.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '_uetvid',
                    $d->months(13),
                    \__('Tells the visitors apart, for the Microsoft ads.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '_uetmsclkid',
                    $d->days(90),
                    \__('Stores the click on a Microsoft ad.', 'italy-cookie-choices')
                ),
            ],
            VendorId::PINTEREST => [
                $this->cookie(
                    '_pin_unauth',
                    $d->years(1),
                    \__(
                        'Groups the actions of the visitors not matched to a Pinterest account.',
                        'italy-cookie-choices'
                    )
                ),
                $this->cookie(
                    '_epik',
                    $d->years(1),
                    \__('Stores the click on a Pinterest ad.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '_derived_epik',
                    $d->years(1),
                    \__('Stores the matches made without cookies.', 'italy-cookie-choices')
                ),
            ],
            VendorId::SNAPCHAT => [
                $this->cookie(
                    '_scid',
                    $d->months(13),
                    \__('Identifies the visitor, to measure the Snapchat ads.', 'italy-cookie-choices')
                ),
            ],
            VendorId::REDDIT => [
                $this->cookie(
                    '_rdt_uuid',
                    $d->days(90),
                    \__('Tells the visitors apart, for the Reddit ad conversions.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '_rdt_cid',
                    $d->days(90),
                    \__('Stores the click on a Reddit ad.', 'italy-cookie-choices')
                ),
            ],
            VendorId::HUBSPOT => [
                $this->cookie(
                    '__hstc',
                    $d->months(6),
                    \__('Tracks the visitor and the times of the visits.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    'hubspotutk',
                    $d->months(6),
                    \__('Identifies the visitor and links the forms sent.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '__hssc',
                    $d->minutes(30),
                    \__('Tracks the session.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '__hssrc',
                    $d->session(),
                    \__('Detects a new session after a restart of the browser.', 'italy-cookie-choices')
                ),
            ],
            VendorId::MATOMO_CLOUD => [
                $this->cookie(
                    '_pk_id.*',
                    $d->months(13),
                    \__('Stores the id of the visitor.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '_pk_ses.*',
                    $d->minutes(30),
                    \__('Keeps the data of the current visit.', 'italy-cookie-choices')
                ),
                $this->cookie(
                    '_pk_ref.*',
                    $d->months(6),
                    \__('Stores the site that brought the visitor.', 'italy-cookie-choices')
                ),
            ],
            VendorId::GOOGLE_RECAPTCHA => [
                $this->cookie(
                    '_GRECAPTCHA',
                    $d->months(6),
                    \__('Analyzes the risk, to protect the forms from spam and bots.', 'italy-cookie-choices')
                ),
            ],
            VendorId::CALENDLY => [
                $this->cookie(
                    '_calendly_session',
                    $d->session(),
                    \__('Keeps the choices of the visitor while booking.', 'italy-cookie-choices')
                ),
            ],
            VendorId::MAILCHIMP => [
                $this->cookie(
                    'MCPopupClosed',
                    $d->years(1),
                    \__('Remembers that the visitor closed the sign-up form.', 'italy-cookie-choices')
                ),
            ],
            VendorId::DISQUS => [
                $this->cookie(
                    'disqus_unique',
                    $d->session(),
                    \__('Counts the visitors of the comments who are not logged in.', 'italy-cookie-choices')
                ),
            ],
            VendorId::DAILYMOTION => [
                $this->cookie(
                    'v1st',
                    $d->months(13),
                    \__(
                        'Tells the visitors apart, for the statistics and the ads of the videos.',
                        'italy-cookie-choices'
                    )
                ),
                $this->cookie(
                    'dmvk',
                    $d->session(),
                    \__('Keeps the video playing without breaks.', 'italy-cookie-choices')
                ),
            ],
            VendorId::TWITCH => [
                $this->cookie(
                    'api_token',
                    $d->months(1),
                    \__('Protects the session of the video player.', 'italy-cookie-choices')
                ),
            ],
        ];
    }
}
