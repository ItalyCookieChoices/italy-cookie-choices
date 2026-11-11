<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI\Cli\Middlewares;

use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Console\Pipeline\StatusCode;
use ItalyCookieChoices\Settings\UI\Cli\SettingsExitCode;
use ItalyStrap\Pipeline\HandlerInterface;
use ItalyStrap\Pipeline\MiddlewareInterface;

/**
 * Reads the JSON file of the first argument for the next ones.
 */
final readonly class ReadSettingsFile implements MiddlewareInterface
{
    public const FILE = 'settings_file';

    public function process(object $message, HandlerInterface $handler): mixed
    {
        \assert($message instanceof Message);
        $path = $message->io()->argument(0) ?? '';
        if ($path === '' || !\is_readable($path)) {
            return StatusCode::FileNotReadable;
        }

        $file = \json_decode((string) \file_get_contents($path), true);
        if (!\is_array($file)) {
            return SettingsExitCode::InvalidSettingsFile;
        }

        $message->set(self::FILE, $file);

        return $handler->handle($message);
    }
}
