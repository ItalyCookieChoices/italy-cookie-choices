<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\UI;

use ItalyCookieChoices\Blocking\UI\BlockingFilters;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Prints Google Analytics, Google Tag Manager and the Facebook pixel from their saved ids,
 * blocked until the consent to their category.
 *
 * In the advanced Consent Mode the Google tags load before the consent, the signals deny the storage.
 */
final readonly class TrackingTags implements SubscriberInterface
{
    public const GOOGLE_ANALYTICS = 'icc-google-analytics';
    public const GOOGLE_TAG_MANAGER = 'icc-google-tag-manager';
    public const FACEBOOK_PIXEL = 'icc-facebook-pixel';

    public function __construct(
        private IntegrationSettings $settings,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_enqueue_scripts' => 'enqueue';
    }

    public function enqueue(): void
    {
        $this->enqueueGoogleAnalytics($this->settings->measurementId());
        $this->enqueueGoogleTagManager($this->settings->containerId());
        $this->enqueueFacebookPixel($this->settings->pixelId());
    }

    private function enqueueGoogleAnalytics(string $id): void
    {
        if ($id === '') {
            return;
        }

        $this->enqueueTag(
            self::GOOGLE_ANALYTICS,
            \add_query_arg('id', $id, 'https://www.googletagmanager.com/gtag/js'),
            \sprintf(
                'window.dataLayer = window.dataLayer || [];'
                . 'window.gtag = window.gtag || function () { window.dataLayer.push( arguments ); };'
                . 'gtag( "js", new Date() );'
                . 'gtag( "config", %s );',
                (string) \wp_json_encode($id)
            ),
            $this->googleCategory(Category::STATISTICS)
        );
    }

    private function enqueueGoogleTagManager(string $id): void
    {
        if ($id === '') {
            return;
        }

        $this->enqueueTag(
            self::GOOGLE_TAG_MANAGER,
            \add_query_arg('id', $id, 'https://www.googletagmanager.com/gtm.js'),
            'window.dataLayer = window.dataLayer || [];'
            . 'window.dataLayer.push( { "gtm.start": new Date().getTime(), event: "gtm.js" } );',
            $this->googleCategory(Category::MARKETING)
        );
    }

    private function enqueueFacebookPixel(string $id): void
    {
        if ($id === '') {
            return;
        }

        $this->enqueueTag(
            self::FACEBOOK_PIXEL,
            'https://connect.facebook.net/en_US/fbevents.js',
            '!function(f){if(f.fbq)return;var n=f.fbq=function(){n.callMethod?'
            . 'n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;'
            . 'n.push=n;n.loaded=!0;n.version="2.0";n.queue=[]}(window);'
            . \sprintf('fbq( "init", %s );fbq( "track", "PageView" );', (string) \wp_json_encode($id)),
            Category::MARKETING
        );
    }

    /**
     * The category that blocks a Google tag, none in the advanced Consent Mode.
     */
    private function googleCategory(string $category): string
    {
        return $this->settings->isConsentModeAdvanced() ? '' : $category;
    }

    /**
     * The script with the code that queues the calls before it loads.
     *
     * @param non-empty-string $handle
     */
    private function enqueueTag(string $handle, string $source, string $before, string $category): void
    {
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- The version breaks the tag URL.
        \wp_enqueue_script($handle, $source, [], null, ['strategy' => 'async']);
        \wp_add_inline_script($handle, $before, 'before');
        if ($category === '') {
            return;
        }

        \wp_script_add_data($handle, BlockingFilters::SCRIPT_CATEGORY, $category);
    }
}
