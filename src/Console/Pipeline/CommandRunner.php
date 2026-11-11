<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Console\Pipeline;

use ItalyStrap\Pipeline\HandlerInterface;
use ItalyStrap\Pipeline\MiddlewareInterface;
use ItalyStrap\Pipeline\Pipeline;

/**
 * Runs the pipeline of a command, a new one each time: the middlewares in order, then the handler.
 *
 * A middleware stops the command returning an ExitCode, without calling the next one.
 * The runner writes the error of a failure and exits with the code.
 */
final readonly class CommandRunner
{
    public function run(Message $message, HandlerInterface $handler, MiddlewareInterface ...$middlewares): ExitCode
    {
        $result = $this->result($message, $handler, ...$middlewares);
        if ($result !== StatusCode::Success) {
            $message->io()->error($result->message());
        }

        $message->io()->exit($result->code());

        return $result;
    }

    private function result(
        Message $message,
        HandlerInterface $handler,
        MiddlewareInterface ...$middlewares
    ): ExitCode {
        try {
            $result = (new Pipeline($handler, ...$middlewares))->handle($message);
        } catch (\Throwable $exception) {
            $message->io()->error($exception->getMessage());

            return StatusCode::Failure;
        }

        return $result instanceof ExitCode ? $result : StatusCode::Failure;
    }
}
