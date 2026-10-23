<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\UI;

use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\ConsentLog\Domain\ConsentId;
use ItalyCookieChoices\ConsentLog\Domain\ConsentRecord;
use ItalyCookieChoices\ConsentLog\Infrastructure\DatabaseConsentLog;
use ItalyCookieChoices\ConsentLog\Infrastructure\LogTable;
use ItalyCookieChoices\ConsentLog\UI\ConsentLogExport;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentLogExportTest extends WPTestCase
{
    public function testItAnswersTheAdminPostAction(): void
    {
        $this->assertSame(
            ['admin_post_italy_cookie_choices_export_consent_log' => 'export'],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testTheUrlHasTheNonce(): void
    {
        $url = $this->makeSut()->url();

        $this->assertStringContainsString('admin-post.php?action=italy_cookie_choices_export_consent_log', $url);
        $this->assertStringNotContainsString('&amp;', $url);
        \parse_str((string) \wp_parse_url($url, \PHP_URL_QUERY), $query);
        $nonce = $query['_wpnonce'] ?? '';
        $this->assertIsString($nonce);
        $this->assertSame(1, \wp_verify_nonce($nonce, ConsentLogExport::ACTION));
    }

    public function testItWritesTheLogAsCsv(): void
    {
        global $wpdb;
        /** @var \wpdb $wpdb */
        $log = new DatabaseConsentLog($wpdb, new LogTable($wpdb));
        $log->add($this->record(['necessary', 'statistics'], '2026-10-01T10:00:00+00:00'));
        $log->add($this->record([], '2026-10-02T10:00:00+00:00'));
        $stream = \fopen('php://memory', 'w+b');
        $this->assertIsResource($stream);

        $this->makeSut()->write($stream);

        \rewind($stream);
        $this->assertSame(
            "consent_id,recorded_at,policy_version,categories,withdrawn\n"
            . "3f2504e0-4f89-41d3-9a0c-0305e82c3301,2026-10-01T10:00:00+00:00,1,\"necessary statistics\",0\n"
            . "3f2504e0-4f89-41d3-9a0c-0305e82c3301,2026-10-02T10:00:00+00:00,1,,1\n",
            \stream_get_contents($stream)
        );
    }

    public function testOnlyTheAdministratorsExport(): void
    {
        $userId = self::factory()->user->create(['role' => 'editor']);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);

        $this->expectException(\WPDieException::class);

        $this->makeSut()->export();
    }

    public function testTheExportNeedsTheNonce(): void
    {
        $userId = self::factory()->user->create(['role' => 'administrator']);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);
        $_REQUEST['_wpnonce'] = 'invalid';

        try {
            $this->expectException(\WPDieException::class);
            $this->makeSut()->export();
        } finally {
            unset($_REQUEST['_wpnonce']);
        }
    }

    /**
     * @param list<string> $categories
     */
    private function record(array $categories, string $date): ConsentRecord
    {
        return new ConsentRecord(
            new ConsentId('3f2504e0-4f89-41d3-9a0c-0305e82c3301'),
            $categories,
            new PolicyVersion('1'),
            new \DateTimeImmutable($date)
        );
    }

    private function makeSut(): ConsentLogExport
    {
        global $wpdb;
        /** @var \wpdb $wpdb */

        return new ConsentLogExport(new DatabaseConsentLog($wpdb, new LogTable($wpdb)));
    }
}
