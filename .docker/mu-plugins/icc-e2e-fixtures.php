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
