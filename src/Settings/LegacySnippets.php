<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings;

/**
 * Reads the texts that identify the scripts in the 2.x textareas, where whole snippets were split by a separator.
 *
 * A snippet with a src gives its URL, without the protocol and the query.
 * An inline snippet gives its longest piece of code, the 2.x wildcard splits the pieces.
 */
final readonly class LegacySnippets
{
    private const SEPARATOR = '<---------SEP--------->';
    private const WILDCARD = '<---------SOMETHING--------->';
    private const MINIMUM_LENGTH = 4;

    /**
     * @return list<string>
     */
    public function texts(string $snippets): array
    {
        $texts = [];
        foreach (\explode(self::SEPARATOR, $snippets) as $snippet) {
            $text = $this->textOf(\trim($snippet));
            if (\strlen($text) < self::MINIMUM_LENGTH) {
                continue;
            }

            $texts[] = $text;
        }

        return \array_values(\array_unique($texts));
    }

    private function textOf(string $snippet): string
    {
        if (\preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $snippet, $matches) === 1) {
            return $this->sourceOf($matches[1]);
        }

        // Not wp_strip_all_tags(), it removes the code between the script tags too.
        $pieces = \array_map(
            static fn(string $piece): string => \trim(\strip_tags($piece)),
            \explode(self::WILDCARD, $snippet)
        );
        \usort($pieces, static fn(string $first, string $second): int => \strlen($second) <=> \strlen($first));

        return $pieces[0];
    }

    private function sourceOf(string $url): string
    {
        $url = (string) \preg_replace('#^([a-z][a-z0-9+.-]*:)?//#i', '', \trim($url));

        return \explode('#', \explode('?', $url)[0])[0];
    }
}
