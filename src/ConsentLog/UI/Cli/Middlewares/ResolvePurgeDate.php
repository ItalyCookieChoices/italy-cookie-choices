<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI\Cli\Middlewares;

use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\ConsentLog\Application\LogSettings;
use ItalyCookieChoices\ConsentLog\UI\Cli\LogExitCode;
use ItalyStrap\Pipeline\HandlerInterface;
use ItalyStrap\Pipeline\MiddlewareInterface;

/**
 * The date before which the records are deleted: --all, --before or the end of the retention.
 */
final readonly class ResolvePurgeDate implements MiddlewareInterface
{
    public const BEFORE = 'purge_before';

    private const ALL = 'all';
    private const OPTION = 'before';
    private const FORMAT = '!Y-m-d';

    public function __construct(
        private LogSettings $settings,
    ) {
    }

    public function process(object $message, HandlerInterface $handler): mixed
    {
        \assert($message instanceof Message);
        $before = $this->before($message);
        if ($before === null) {
            return LogExitCode::InvalidDate;
        }

        $message->set(self::BEFORE, $before);

        return $handler->handle($message);
    }

    private function before(Message $message): ?\DateTimeImmutable
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($message->io()->flag(self::ALL)) {
            // Also the records of this second.
            return $now->modify('+1 day');
        }

        $date = $message->io()->option(self::OPTION);
        if ($date === null) {
            return $this->settings->retention()->cutoff($now);
        }

        return $this->date($date);
    }

    /**
     * The start of the day in the timezone of the site, null when it is not a date.
     */
    private function date(string $date): ?\DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat(self::FORMAT, $date, \wp_timezone());
        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            return null;
        }

        return $parsed;
    }
}
