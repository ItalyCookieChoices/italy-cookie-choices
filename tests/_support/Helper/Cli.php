<?php

declare(strict_types=1);

namespace Helper;

use Codeception\Module;
use PHPUnit\Framework\Assert;
use Symfony\Component\Process\Process;

/**
 * Runs the wp binary of the container on the site of the e2e suite.
 *
 * The WPCLI module of wp-browser checks the site when the actors are built,
 * the static analysis builds them where there is no site: this module checks nothing before a test runs it.
 */
final class Cli extends Module
{
    /**
     * @var array{path: string, bin: string}
     */
    protected array $config = [
        'path' => '/var/www/html',
        'bin' => 'wp',
    ];

    private ?Process $last = null;

    /**
     * Runs `wp` with the arguments, like ['cookie-choices', 'status'], and returns the exit code.
     *
     * @param list<string> $arguments
     */
    public function cli(array $arguments): int
    {
        $this->last = new Process([$this->config['bin'], ...$arguments, '--path=' . $this->config['path']]);
        $this->last->run();

        return (int) $this->last->getExitCode();
    }

    public function grabLastShellOutput(): string
    {
        return $this->lastProcess()->getOutput();
    }

    public function grabLastShellErrorOutput(): string
    {
        return $this->lastProcess()->getErrorOutput();
    }

    public function seeInShellOutput(string $text): void
    {
        Assert::assertStringContainsString($text, $this->grabLastShellOutput());
    }

    private function lastProcess(): Process
    {
        if (!$this->last instanceof Process) {
            Assert::fail('No wp command ran');
        }

        return $this->last;
    }
}
