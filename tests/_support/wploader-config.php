<?php

/**
 * Loaded by WPLoader before WordPress.
 *
 * The test bootstrap requires the deprecated class-phpmailer.php, the notice goes to STDERR
 * and Infection stops the initial test run on the first STDERR output.
 */

declare(strict_types=1);

$GLOBALS['wp_filter']['deprecated_file_trigger_error'][10]['icc_tests'] = [
    'function' => '__return_false',
    'accepted_args' => 1,
];
