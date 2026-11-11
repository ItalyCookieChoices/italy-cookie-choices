<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI\Cli\Handlers;

use ItalyCookieChoices\Console\Pipeline\ExitCode;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Console\Pipeline\StatusCode;
use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyCookieChoices\ConsentLog\UI\Cli\LogExitCode;
use ItalyCookieChoices\ConsentLog\UI\Cli\Middlewares\ResolvePurgeDate;
use ItalyStrap\Pipeline\HandlerInterface;

/**
 * Deletes the records before the date of ResolvePurgeDate, after the confirmation.
 */
final readonly class PurgeLog implements HandlerInterface
{
    public function __construct(
        private ConsentLog $log,
    ) {
    }

    public function handle(object $message): ExitCode
    {
        \assert($message instanceof Message);
        $before = $message->get(ResolvePurgeDate::BEFORE);
        if (!$before instanceof \DateTimeImmutable) {
            return LogExitCode::InvalidDate;
        }

        $io = $message->io();
        if (!$io->confirm($this->question($before))) {
            return StatusCode::Cancelled;
        }

        $deleted = $this->log->deleteBefore($before);
        $io->success(\sprintf(
            /* translators: %d: a number of records. */
            \_n('%d record deleted.', '%d records deleted.', $deleted, 'italy-cookie-choices'),
            $deleted
        ));

        return StatusCode::Success;
    }

    private function question(\DateTimeImmutable $before): string
    {
        return \sprintf(
            /* translators: %s: a date and time. */
            \__('Delete the records of the consent log before %s?', 'italy-cookie-choices'),
            \wp_date('Y-m-d H:i', $before->getTimestamp())
        );
    }
}
