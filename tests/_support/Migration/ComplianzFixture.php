<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Migration;

/**
 * The options and the tables of Complianz 7, with the columns the import reads.
 * In the integration tests the tables are temporary, dropped at the end of each test.
 */
final class ComplianzFixture
{
    /**
     * @param array<string, mixed> $options The values of cmplz_options.
     * @param array<string, mixed> $banner The columns of the default banner, the arrays are serialized.
     */
    public static function install(array $options, array $banner = []): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        \update_option('cmplz_options', $options);
        \update_option('cmplz_cbdb_version', '7.5.7');
        \update_option('cmplz_cookietable_version', '7.5.7');
        \update_option('cmplz_wizard_completed_once', true);

        $banners = $wpdb->prefix . 'cmplz_cookiebanners';
        $wpdb->query("DROP TABLE IF EXISTS $banners");
        $wpdb->query("CREATE TABLE $banners (
            ID int(11) NOT NULL AUTO_INCREMENT,
            `default` int(11) NOT NULL DEFAULT 0,
            position text, use_logo text, logo_attachment_id text, `revoke` text, manage_consent_options text,
            dismiss text, save_preferences text, view_preferences text, accept text, message_optin text,
            category_functional text, category_prefs text, category_stats text, category_all text,
            functional_text text, preferences_text text, statistics_text text, marketing_text text,
            disable_cookiebanner text, soft_cookiewall text, dismiss_on_scroll text, dismiss_on_timeout text,
            use_custom_cookie_css text, custom_css text, colorpalette_background text, colorpalette_text text,
            PRIMARY KEY (ID)
        )");
        $wpdb->insert($banners, ['default' => 0, 'accept' => 'Not the default']);
        $wpdb->insert($banners, ['default' => 1, ...\array_map(\maybe_serialize(...), $banner)]);

        $cookies = $wpdb->prefix . 'cmplz_cookies';
        $wpdb->query("DROP TABLE IF EXISTS $cookies");
        $wpdb->query("CREATE TABLE $cookies (
            ID int(11) NOT NULL AUTO_INCREMENT, name varchar(250), retention text, serviceID int(11),
            cookieFunction text, purpose text, deleted int(11) DEFAULT 0, ignored int(11) DEFAULT 0,
            showOnPolicy int(11) DEFAULT 1, isTranslationFrom int(11) DEFAULT 0, isOwnDomainCookie int(11) DEFAULT 0,
            PRIMARY KEY (ID)
        )");

        $services = $wpdb->prefix . 'cmplz_services';
        $wpdb->query("DROP TABLE IF EXISTS $services");
        $wpdb->query("CREATE TABLE $services (
            ID int(11) NOT NULL AUTO_INCREMENT, name varchar(250), PRIMARY KEY (ID)
        )");
    }

    public static function addService(string $name): int
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $wpdb->insert($wpdb->prefix . 'cmplz_services', ['name' => $name]);

        return (int) $wpdb->insert_id;
    }

    /**
     * @param array<string, mixed> $columns
     */
    public static function addCookie(array $columns): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $wpdb->insert($wpdb->prefix . 'cmplz_cookies', $columns);
    }
}
