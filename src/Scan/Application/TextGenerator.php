<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

/**
 * A generator of text with the AI provider of the site owner, for the scan assistant.
 */
interface TextGenerator
{
    /**
     * Whether the site has an AI provider that generates text.
     */
    public function isAvailable(): bool;

    /**
     * The JSON object the provider returns for the prompt, in the shape of the schema.
     *
     * @param array<string, mixed> $schema The JSON schema of the reply.
     * @return array<array-key, mixed>
     * @throws \RuntimeException When the provider fails or its reply is not a JSON object.
     */
    public function json(string $instruction, string $prompt, array $schema): array;
}
