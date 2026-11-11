<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support;

use ItalyCookieChoices\Console\IO\IOFactory;
use ItalyCookieChoices\Console\IO\IOInterface;

/**
 * Gives the commands a FakeIO with the arguments WP-CLI would pass, the test reads it after the run.
 */
final class FakeIOFactory implements IOFactory
{
    private ?FakeIO $io = null;

    /**
     * @param bool $confirms The answer to the questions.
     */
    public function __construct(
        private readonly bool $confirms = true,
    ) {
    }

    public function create(array $arguments, array $options): IOInterface
    {
        $this->io = new FakeIO($arguments, $options, $this->confirms);

        return $this->io;
    }

    public function io(): FakeIO
    {
        if (!$this->io instanceof FakeIO) {
            throw new \LogicException('No command ran');
        }

        return $this->io;
    }
}
