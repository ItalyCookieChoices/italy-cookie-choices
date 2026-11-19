<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Migration;

/**
 * The table of GDPR Cookie Compliance, one row for each setting, the values serialized like the plugin does.
 * In the integration tests the table is temporary, dropped at the end of each test.
 */
final class GdprCookieComplianceFixture
{
    /**
     * @param array<string, mixed> $settings
     */
    public static function install(array $settings): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        \update_option('gdpr_cc_db_created', true);
        $table = $wpdb->prefix . 'gdpr_cc_options';
        $wpdb->query("DROP TABLE IF EXISTS $table");
        $wpdb->query("CREATE TABLE $table (
            id int(11) NOT NULL AUTO_INCREMENT, option_key varchar(191) NOT NULL, option_value longtext,
            site_id int(11), extras longtext, PRIMARY KEY (id)
        )");
        foreach ($settings as $key => $value) {
            $wpdb->insert($table, ['option_key' => $key, 'option_value' => \maybe_serialize($value), 'site_id' => 1]);
        }
    }
}
