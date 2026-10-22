<?php
/**
 * Plugin Name: Italy Cookie Choices e2e fixtures
 * Description: Prints third party like scripts in head and footer when the e2e suite asks for them.
 */

declare(strict_types=1);

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
