<?php
/**
 * Plugin Name: Italy Cookie Choices e2e fixtures
 * Description: Prints third party like scripts in head and footer when the e2e suite asks for them.
 */

declare(strict_types=1);

// The requests left by a test run with the first requests of the next one after the reset of the database,
// their database errors, like a deadlock or a table created twice, are harmless but printed before the headers
// they break the page. They still go to the error log (#313).
$GLOBALS['wpdb']->hide_errors();

add_action('wp_head', static function (): void {
    if (!get_option('icc_e2e_scripts')) {
        return;
    }

    echo '<script id="icc-head-tracker">window.iccHeadTracker = true;</script>';
});

add_action('wp_footer', static function (): void {
    if (!get_option('icc_e2e_scripts')) {
        return;
    }

    echo '<script id="icc-footer-tracker">window.iccFooterTracker = true;</script>';
}, 1);

// The browser sends the Global Privacy Control signal, Chrome has no setting for it.
add_action('wp_head', static function (): void {
    if (!get_option('icc_e2e_gpc')) {
        return;
    }

    echo '<script id="icc-gpc">Object.defineProperty(navigator, "globalPrivacyControl", {value: true});</script>';
}, 0);

// Records the consent events from the first line of the page, before the consent script runs.
add_action('wp_head', static function (): void {
    if (!get_option('icc_e2e_events')) {
        return;
    }

    echo '<script id="icc-events-recorder">window.iccEvents = [];'
        . '["italy-cookie-choices:ready", "italy-cookie-choices:consent-changed"].forEach(function (name) {'
        . 'document.addEventListener(name, function (event) { window.iccEvents.push({name: name, detail: event.detail}); });'
        . '});</script>';
}, 0);

add_action(
    \ItalyCookieChoices\Consent\Application\BuildingConsentPolicy::class,
    static function (\ItalyCookieChoices\Consent\Application\BuildingConsentPolicy $event): void {
        $e2eVersion = get_option('icc_e2e_policy_version');
        if (!is_string($e2eVersion) || $e2eVersion === '') {
            return;
        }

        $event->changeVersion(new \ItalyCookieChoices\Consent\Domain\PolicyVersion($e2eVersion));
    }
);

// A vendor served by the site, its script runs only after the consent to the statistics.
add_action(
    \ItalyCookieChoices\Blocking\Application\BuildingVendors::class,
    static function (\ItalyCookieChoices\Blocking\Application\BuildingVendors $event): void {
        if (!get_option('icc_e2e_enqueued')) {
            return;
        }

        $event->addVendor(new \ItalyCookieChoices\Blocking\Domain\Vendor('icc-e2e', 'statistics', ['icc-e2e/tracker.js']));
    }
);

add_action('wp_enqueue_scripts', static function (): void {
    if (!get_option('icc_e2e_enqueued')) {
        return;
    }

    $url = content_url('mu-plugins/icc-e2e/');
    wp_enqueue_script('icc-e2e-tracker', $url . 'tracker.js', [], null);
    wp_add_inline_script('icc-e2e-tracker', 'window.iccTrackerQueue = ["inline"];', 'before');
    wp_enqueue_script('icc-e2e-tagged', $url . 'tagged.js', [], null);
    wp_script_add_data('icc-e2e-tagged', 'consent_category', 'preferences');
});

// The Google tags served from the site by the Google tag gateway, the addresses answer 404.
add_action('wp_enqueue_scripts', static function (): void {
    if (!get_option('icc_e2e_gateway')) {
        return;
    }

    wp_enqueue_script('icc-e2e-gateway-analytics', home_url('/metrics/?id=G-ICCTEST'), [], null);
    wp_enqueue_script('icc-e2e-gateway-manager', home_url('/metrics/?id=GTM-ICCTEST'), [], null);
    wp_enqueue_script('icc-e2e-gateway-tag', home_url('/metrics/tag.js'), [], null);
});

// The YouTube oEmbed without asking YouTube.
add_filter('pre_oembed_result', static function (mixed $result, string $url): mixed {
    if (!str_contains($url, 'icc-oembed')) {
        return $result;
    }

    return '<iframe width="560" height="315" src="https://www.youtube.com/embed/icc-oembed"></iframe>';
}, 10, 2);

// The screenshots show no update notice of WordPress, of the plugins or of the themes.
foreach (['update_core', 'update_plugins', 'update_themes'] as $iccTransient) {
    add_filter('pre_site_transient_' . $iccTransient, static function (mixed $value): mixed {
        if (!get_option('icc_e2e_screenshots')) {
            return $value;
        }

        return (object) [
            'last_checked' => time(),
            'version_checked' => wp_get_wp_version(),
            'updates' => [],
            'response' => [],
            'translations' => [],
        ];
    });
}

// The answers of wp_has_consent() in PHP, read by the WP Consent API e2e tests.
add_action('wp_footer', static function (): void {
    if (!get_option('icc_e2e_wp_consent_api') || !function_exists('wp_has_consent')) {
        return;
    }

    $categories = ['functional', 'preferences', 'statistics', 'statistics-anonymous', 'marketing'];
    $consent = array_combine($categories, array_map('wp_has_consent', $categories));
    printf('<script id="icc-e2e-wp-has-consent" type="application/json">%s</script>', wp_json_encode($consent));
});

// A renewal of the consent cookie answered after the end of a test would set the cookie in the next one.
add_filter('rest_endpoints', static function (array $endpoints): array {
    if (get_option('icc_e2e_renewal')) {
        return $endpoints;
    }

    unset($endpoints['/italy-cookie-choices/v1/consent-cookie']);

    return $endpoints;
});

// A cookie registered by another plugin with the WP Consent API, for the cookie table.
add_action('init', static function (): void {
    if (!get_option('icc_e2e_cookie_info') || !function_exists('wp_add_cookie_info')) {
        return;
    }

    wp_add_cookie_info('icc_e2e_api', 'E2E plugin', 'marketing', '1 year', 'Tests the cookie table.');
});
