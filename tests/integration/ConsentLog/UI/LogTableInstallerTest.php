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
