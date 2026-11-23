<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Support\Scan;

use ItalyCookieChoices\Scan\Application\TextGenerator;

/**
 * The AI provider in the tests: it returns the reply it is given, or fails, and keeps the prompts.
 */
final class FakeTextGenerator implements TextGenerator
{
    /** @var list<string> */
    public array $prompts = [];

    /**
     * @param array<array-key, mixed>|null $reply Null fails like a provider that is down.
     */
    public function __construct(
        private readonly ?array $reply = ['hosts' => [], 'cookies' => []],
        private readonly bool $isAvailable = true,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function json(string $instruction, string $prompt, array $schema): array
    {
        $this->prompts[] = $prompt;
        if ($this->reply === null) {
            throw new \RuntimeException('The provider is down.');
        }

        return $this->reply;
    }
}
