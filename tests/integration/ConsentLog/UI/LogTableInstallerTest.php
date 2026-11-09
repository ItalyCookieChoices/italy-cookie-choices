<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\UI;

use ItalyCookieChoices\ConsentLog\Infrastructure\LogTable;
use ItalyCookieChoices\ConsentLog\UI\LogTableInstaller;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class LogTableInstallerTest extends WPTestCase
{
    public function testItInstallsWhenThePluginsAreLoaded(): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */

        $this->assertSame(
            ['plugins_loaded' => 'install'],
            \iterator_to_array((new LogTableInstaller(new LogTable($wpdb)))->getSubscribedEvents())
        );
    }

    public function testItInstallsAnOldTable(): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        \update_option(LogTable::VERSION_OPTION, 0);

        (new LogTableInstaller(new LogTable($wpdb)))->install();

        $this->assertSame(LogTable::VERSION, \get_option(LogTable::VERSION_OPTION));
    }

    /**
     * Two requests after an update can both create the table: the second one does not find it, then fails
     * because the first one created it meanwhile. The error must not be printed, before the headers it breaks
     * the page. The tests of WordPress create temporary tables, so the race is checked by its cause.
     */
    public function testTheErrorsOfTheDatabaseAreNotPrintedWhileTheTableIsCreated(): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        \update_option(LogTable::VERSION_OPTION, 0);
        $suppressed = null;
        \add_filter('dbdelta_queries', static function (array $queries) use ($wpdb, &$suppressed): array {
            $suppressed = $wpdb->suppress_errors;

            return $queries;
        });
        $wpdb->suppress_errors(false);

        (new LogTableInstaller(new LogTable($wpdb)))->install();

        $this->assertTrue($suppressed);
        $this->assertFalse($wpdb->suppress_errors);
    }

    public function testACurrentTableIsLeftAlone(): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        \update_option(LogTable::VERSION_OPTION, LogTable::VERSION);
        $queries = [];
        \add_filter('query', static function (string $query) use (&$queries): string {
            $queries[] = $query;

            return $query;
        });

        (new LogTableInstaller(new LogTable($wpdb)))->install();

        $this->assertSame([], \preg_grep('/CREATE TABLE|DESCRIBE/i', $queries));
    }
}
