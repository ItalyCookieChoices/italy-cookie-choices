<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Console\Pipeline;

use ItalyCookieChoices\Console\Pipeline\CommandRunner;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Console\Pipeline\StatusCode;
use ItalyCookieChoices\Tests\Support\FakeIO;
use ItalyStrap\Pipeline\CallbackHandler;
use ItalyStrap\Pipeline\HandlerInterface;
use ItalyStrap\Pipeline\MiddlewareInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CommandRunnerTest extends WPTestCase
{
    public function testTheMiddlewaresRunInOrderThenTheHandler(): void
    {
        $io = new FakeIO();

        $code = (new CommandRunner())->run(
            new Message($io),
            new CallbackHandler(static function (object $message): StatusCode {
                \assert($message instanceof Message);
                $steps = \array_filter((array) $message->get('steps'), \is_string(...));
                $message->io()->line('handler ' . \implode(',', $steps));

                return StatusCode::Success;
            }),
            $this->step('first'),
            $this->step('second')
        );

        $this->assertSame(StatusCode::Success, $code);
        $this->assertSame(['handler first,second'], $io->lines);
        $this->assertSame([], $io->errors);
        $this->assertSame(0, $io->exit);
    }

    public function testAMiddlewareStopsTheCommandWithItsCode(): void
    {
        $io = new FakeIO();

        $code = (new CommandRunner())->run(
            new Message($io),
            new CallbackHandler(static fn(): StatusCode => StatusCode::Success),
            new class implements MiddlewareInterface {
                public function process(object $message, HandlerInterface $handler): StatusCode
                {
                    return StatusCode::FileNotReadable;
                }
            }
        );

        $this->assertSame(StatusCode::FileNotReadable, $code);
        $this->assertSame(['The file cannot be read.'], $io->errors);
        $this->assertSame(4, $io->exit);
    }

    public function testAnExceptionIsAFailureWithItsMessage(): void
    {
        $io = new FakeIO();

        $code = (new CommandRunner())->run(
            new Message($io),
            new CallbackHandler(static function (): never {
                throw new \RuntimeException('Broken');
            })
        );

        $this->assertSame(StatusCode::Failure, $code);
        $this->assertSame(['Broken', 'The command failed.'], $io->errors);
        $this->assertSame(1, $io->exit);
    }

    public function testAResultThatIsNotAnExitCodeIsAFailure(): void
    {
        $io = new FakeIO();

        $this->assertSame(
            StatusCode::Failure,
            (new CommandRunner())->run(new Message($io), new CallbackHandler(static fn(): string => 'done'))
        );
    }

    private function step(string $name): MiddlewareInterface
    {
        return new class ($name) implements MiddlewareInterface {
            public function __construct(
                private readonly string $name,
            ) {
            }

            public function process(object $message, HandlerInterface $handler): mixed
            {
                \assert($message instanceof Message);
                $steps = (array) $message->get('steps');
                $steps[] = $this->name;
                $message->set('steps', $steps);

                return $handler->handle($message);
            }
        };
    }
}
