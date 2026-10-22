<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\Infrastructure;

/**
 * The table of the consent log, created and upgraded with dbDelta().
 */
final readonly class LogTable
{
    public const VERSION = 1;
    public const VERSION_OPTION = 'italy_cookie_choices_log_table_version';

    private const NAME = 'icc_consent_log';

    public function __construct(
        private \wpdb $database,
    ) {
    }

    public function name(): string
    {
        return $this->database->prefix . self::NAME;
    }

    public function isCurrent(): bool
    {
        $version = \get_option(self::VERSION_OPTION, 0);

        return \is_numeric($version) && (int) $version >= self::VERSION;
    }

    public function install(): void
    {
        require_once \ABSPATH . 'wp-admin/includes/upgrade.php';

        // dbDelta() wants two spaces after PRIMARY KEY and one field per line.
        \dbDelta(\sprintf(
            "CREATE TABLE %s (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
consent_id char(36) NOT NULL,
categories varchar(255) NOT NULL DEFAULT '',
policy_version varchar(64) NOT NULL,
recorded_at datetime NOT NULL,
PRIMARY KEY  (id),
KEY consent_id (consent_id),
KEY recorded_at (recorded_at)
) %s;",
            $this->name(),
            $this->database->get_charset_collate()
        ));

        \update_option(self::VERSION_OPTION, self::VERSION);
    }

    public function drop(): void
    {
        $this->database->query(\sprintf('DROP TABLE IF EXISTS %s', $this->name())); // phpcs:ignore
        \delete_option(self::VERSION_OPTION);
    }
}
