<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\Domain;

/**
 * The consent categories of the WP Consent API.
 *
 * @link https://wordpress.org/plugins/wp-consent-api/
 */
enum WpConsentCategory: string
{
    case FUNCTIONAL = 'functional';
    case PREFERENCES = 'preferences';
    case STATISTICS = 'statistics';
    case STATISTICS_ANONYMOUS = 'statistics-anonymous';
    case MARKETING = 'marketing';
}
