<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Infrastructure;

use ItalyCookieChoices\Scan\Application\TextGenerator;

/**
 * The AI Client of WordPress 7.0, with the provider set by the site owner in the Connectors screen.
 *
 * On an older WordPress, or without a provider for text, it is not available.
 */
final readonly class AiClientTextGenerator implements TextGenerator
{
    private const PROMPT = 'wp_ai_client_prompt';

    public function isAvailable(): bool
    {
        if (!\function_exists(self::PROMPT) || !\wp_supports_ai()) {
            return false;
        }

        return \wp_ai_client_prompt()->is_supported_for_text_generation();
    }

    public function json(string $instruction, string $prompt, array $schema): array
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException(\__('No AI provider generates text on this site.', 'italy-cookie-choices'));
        }

        $text = \wp_ai_client_prompt($prompt)
            ->using_system_instruction($instruction)
            ->as_json_response($schema)
            ->generate_text();
        if ($text instanceof \WP_Error) {
            throw new \RuntimeException($text->get_error_message());
        }

        $reply = \json_decode($text, true);
        if (!\is_array($reply)) {
            throw new \RuntimeException(\__('The AI provider did not return a valid reply.', 'italy-cookie-choices'));
        }

        return $reply;
    }
}
