<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Application;

use ItalyCookieChoices\Blocking\Domain\CustomScript;
use ItalyCookieChoices\Settings\Options;

/**
 * The custom scripts of the settings, in the order the site owner saved them.
 */
final readonly class CustomScripts
{
    public function __construct(
        private Options $options,
    ) {
    }

    /**
     * @param string $position CustomScript::HEAD or CustomScript::BODY.
     * @return list<CustomScript>
     */
    public function in(string $position): array
    {
        $scripts = [];
        foreach ($this->options->records(BlockingSetting::CUSTOM_SCRIPTS) as $record) {
            $script = $this->script($record);
            if ($script === null || $script->position() !== $position) {
                continue;
            }

            $scripts[] = $script;
        }

        return $scripts;
    }

    /**
     * @param array<array-key, mixed> $record
     */
    private function script(array $record): ?CustomScript
    {
        try {
            return new CustomScript(
                $this->text($record['name'] ?? ''),
                $this->text($record['category'] ?? ''),
                $this->text($record['position'] ?? ''),
                $this->text($record['code'] ?? '')
            );
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function text(mixed $value): string
    {
        return \is_string($value) ? $value : '';
    }
}
