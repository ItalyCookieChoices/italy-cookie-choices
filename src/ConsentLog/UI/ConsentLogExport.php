<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI;

use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Downloads the log as a CSV file, for the site owner who must demonstrate a consent.
 *
 * The link is admin-post.php?action=italy_cookie_choices_export_consent_log with the nonce of url().
 */
final readonly class ConsentLogExport implements SubscriberInterface
{
    public const ACTION = 'italy_cookie_choices_export_consent_log';

    private const CAPABILITY = 'manage_options';
    private const COLUMNS = ['consent_id', 'recorded_at', 'policy_version', 'categories', 'withdrawn'];

    public function __construct(
        private ConsentLog $log,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'admin_post_' . self::ACTION => 'export';
    }

    public function url(): string
    {
        return \wp_nonce_url(\admin_url('admin-post.php?action=' . self::ACTION), self::ACTION);
    }

    public function export(): void
    {
        if (!\current_user_can(self::CAPABILITY)) {
            \wp_die(\esc_html__('You cannot export the consent log.', 'italy-cookie-choices'), '', ['response' => 403]);
        }

        \check_admin_referer(self::ACTION);

        \nocache_headers();
        \header('Content-Type: text/csv; charset=utf-8');
        \header(\sprintf(
            'Content-Disposition: attachment; filename="consent-log-%s.csv"',
            \gmdate('Y-m-d')
        ));

        $output = \fopen('php://output', 'wb');
        if ($output === false) {
            return;
        }

        $this->write($output);
        \fclose($output);
    }

    /**
     * @param resource $stream
     */
    public function write($stream): void
    {
        \fputcsv($stream, self::COLUMNS, ',', '"', '');
        foreach ($this->log->all() as $record) {
            \fputcsv($stream, [
                $record->id()->toString(),
                $record->recordedAt()->format(\DATE_ATOM),
                $record->version()->toString(),
                \implode(' ', $record->categories()),
                $record->isWithdrawal() ? '1' : '0',
            ], ',', '"', '');
        }
    }
}
