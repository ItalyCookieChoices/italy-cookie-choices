<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\Middlewares;

use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Console\Pipeline\StatusCode;
use ItalyStrap\Pipeline\HandlerInterface;
use ItalyStrap\Pipeline\MiddlewareInterface;

/**
 * Opens the file of the --file option, or the standard output, for the next ones, then closes it.
 */
final readonly class OpenOutput implements MiddlewareInterface
{
    public const STREAM = 'output';
    public const FILE = 'file';

    private const STANDARD_OUTPUT = 'php://output';

    public function process(object $message, HandlerInterface $handler): mixed
    {
        \assert($message instanceof Message);
        $file = $message->io()->option(self::FILE);
        $stream = @\fopen($file ?? self::STANDARD_OUTPUT, 'wb'); // phpcs:ignore -- The exit code reports the error.
        if ($stream === false) {
            return StatusCode::FileNotWritable;
        }

        $message->set(self::STREAM, $stream);
        $message->set(self::FILE, $file);

        try {
            return $handler->handle($message);
        } finally {
            \fclose($stream);
        }
    }
}
