<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI\Cli\Handlers;

use ItalyCookieChoices\Console\Middlewares\OpenOutput;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Console\Pipeline\StatusCode;
use ItalyCookieChoices\Settings\SettingsFile;
use ItalyStrap\Pipeline\HandlerInterface;

/**
 * Writes the exported settings in the output opened by OpenOutput.
 */
final readonly class WriteSettingsFile implements HandlerInterface
{
    public function __construct(
        private SettingsFile $file,
    ) {
    }

    public function handle(object $message): StatusCode
    {
        \assert($message instanceof Message);
        $stream = $message->get(OpenOutput::STREAM);
        if (!\is_resource($stream)) {
            return StatusCode::FileNotWritable;
        }

        $json = (string) \wp_json_encode($this->file->export(), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
        \fwrite($stream, $json . "\n");
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
        $message->io()->success(\sprintf(\__('The settings are exported to %s.', 'italy-cookie-choices'), $file));
    }
}
