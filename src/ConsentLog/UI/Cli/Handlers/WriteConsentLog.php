<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI\Cli\Handlers;

use ItalyCookieChoices\Console\Middlewares\OpenOutput;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Console\Pipeline\StatusCode;
use ItalyCookieChoices\ConsentLog\UI\ConsentLogExport;
use ItalyStrap\Pipeline\HandlerInterface;

/**
 * Writes the CSV of the consent log in the output opened by OpenOutput.
 */
final readonly class WriteConsentLog implements HandlerInterface
{
    public function __construct(
        private ConsentLogExport $export,
    ) {
    }

    public function handle(object $message): StatusCode
    {
        \assert($message instanceof Message);
        $stream = $message->get(OpenOutput::STREAM);
        if (!\is_resource($stream)) {
            return StatusCode::FileNotWritable;
        }

        $this->export->write($stream);
        $this->reportTheFile($message);

        return StatusCode::Success;
    }

    private function reportTheFile(Message $message): void
    {
        $file = $message->get(OpenOutput::FILE);
        if (!\is_string($file)) {
            return;
        }

        /* translators: %s: the path of the file. */
        $message->io()->success(\sprintf(\__('The consent log is exported to %s.', 'italy-cookie-choices'), $file));
    }
}
