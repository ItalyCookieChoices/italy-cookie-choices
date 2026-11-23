<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\Application\ScanReports;
use ItalyCookieChoices\Scan\Domain\Report;
use ItalyCookieChoices\Scan\UI\ScanAbilities;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyCookieChoices\Tests\Support\RunsAbilities;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScanAbilitiesTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;
    use RunsAbilities;

    private const SCAN = 'italy-cookie-choices/scan-site';
    private const REPORT = 'italy-cookie-choices/get-scan-report';

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1']);
        // The pages of the site, read without a real loopback request.
        \add_filter('pre_http_request', static fn(): array => [
            'response' => ['code' => 200, 'message' => 'OK'],
            'body' => '<iframe src="https://www.youtube.com/embed/abc"></iframe>'
                . '<script src="https://cdn.tracker.example/t.js"></script>',
            'headers' => [],
            'cookies' => [],
        ]);
    }

    public function testItRegistersWithTheAbilities(): void
    {
        $this->assertSame(
            ['wp_abilities_api_init' => 'register'],
            \iterator_to_array($this->service(ScanAbilities::class)->getSubscribedEvents())
        );
    }

    public function testTheAdministratorsScanTheChosenPages(): void
    {
        $this->logInAs('administrator');
        $ability = $this->ability(self::SCAN);

        $result = $ability->execute(['home' => true]);

        $this->assertSame(
            [
                'total' => 1,
                'truncated' => false,
                'nextOffset' => null,
                'pages' => [[
                    'id' => 0,
                    'url' => \home_url('/'),
                    'vendors' => ['youtube'],
                    'hosts' => ['cdn.tracker.example'],
                    'error' => '',
                ]],
            ],
            $result
        );
        $this->assertTrue($this->annotations($ability)['readonly'] ?? null);
    }

    public function testTheScanReadsTenPagesAtATime(): void
    {
        $this->logInAs('administrator');
        $posts = self::factory()->post->create_many(12, ['post_status' => 'publish']);

        $first = $this->ability(self::SCAN)->execute(['posts' => $posts]);
        $second = $this->ability(self::SCAN)->execute(['posts' => $posts, 'offset' => 10]);

        $this->assertIsArray($first);
        $this->assertSame(12, $first['total'] ?? null);
        $this->assertSame(10, $first['nextOffset'] ?? null);
        $this->assertCount(10, \is_array($first['pages'] ?? null) ? $first['pages'] : []);
        $this->assertIsArray($second);
        $this->assertArrayHasKey('nextOffset', $second);
        $this->assertNull($second['nextOffset']);
        $this->assertCount(2, \is_array($second['pages'] ?? null) ? $second['pages'] : []);
    }

    public function testTheAdministratorsGetTheReportOfTheLastScan(): void
    {
        $this->logInAs('administrator');
        $ability = $this->ability(self::REPORT);
        $this->assertNull($ability->execute());

        $date = new \DateTimeImmutable('2026-11-20T10:00:00+00:00');
        $this->service(ScanReports::class)->save(
            new Report($date, 12, 1, ['youtube'], ['cdn.tracker.example'], ['youtube'], [])
        );

        $this->assertSame(
            [
                'date' => '2026-11-20T10:00:00+00:00',
                'pages' => 12,
                'errors' => 1,
                'vendors' => ['youtube'],
                'hosts' => ['cdn.tracker.example'],
            ],
            $ability->execute()
        );
        $this->assertTrue($this->annotations($ability)['readonly'] ?? null);
    }

    public function testTheOtherUsersCannotScan(): void
    {
        $this->logInAs('editor');

        $this->assertInstanceOf(\WP_Error::class, $this->ability(self::SCAN)->execute(['home' => true]));
        $this->assertInstanceOf(\WP_Error::class, $this->ability(self::REPORT)->execute());
    }
}
