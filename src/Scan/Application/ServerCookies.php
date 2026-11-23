<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\Application;

/**
 * The cookies the server sets with the Set-Cookie headers of a page, HttpOnly included.
 *
 * The removed cookies are skipped, a cookie set twice keeps the last write.
 */
final readonly class ServerCookies
{
    private const HEADER = 'set-cookie:';

    /**
     * @param list<string> $headers The headers of the response, like headers_list() returns them.
     * @return list<array{name: string, path: string, duration: int|null, httpOnly: bool}>
     *     The duration in seconds, null for a session cookie.
     */
    public function from(array $headers, \DateTimeImmutable $now): array
    {
        $cookies = [];
        foreach ($headers as $header) {
            $cookie = $this->cookieOf($header, $now);
            if ($cookie === null) {
                continue;
            }

            unset($cookies[$cookie['name'] . ';' . $cookie['path']]);
            $cookies[$cookie['name'] . ';' . $cookie['path']] = $cookie;
        }

        return \array_values($cookies);
    }

    /**
     * @return array{name: string, path: string, duration: int|null, httpOnly: bool}|null
     */
    private function cookieOf(string $header, \DateTimeImmutable $now): ?array
    {
        if (\stripos($header, self::HEADER) !== 0) {
            return null;
        }

        $parts = \explode(';', \substr($header, \strlen(self::HEADER)));
        $pair = \explode('=', \array_shift($parts), 2);
        $name = \trim($pair[0]);
        if ($name === '' || \count($pair) < 2) {
            return null;
        }

        $attributes = $this->attributes($parts);
        $duration = $this->duration($attributes, $now);
        if ($duration !== null && $duration <= 0) {
            return null;
        }

        return [
            'name' => $name,
            'path' => $attributes['path'] ?? '',
            'duration' => $duration,
            'httpOnly' => \array_key_exists('httponly', $attributes),
        ];
    }

    /**
     * @param list<string> $parts
     * @return array<string, string> By lowercase name.
     */
    private function attributes(array $parts): array
    {
        $attributes = [];
        foreach ($parts as $part) {
            $pair = \explode('=', $part, 2);
            $attributes[\strtolower(\trim($pair[0]))] = \trim($pair[1] ?? '');
        }

        return $attributes;
    }

    /**
     * Max-Age wins over Expires, as in the browsers.
     *
     * @param array<string, string> $attributes
     */
    private function duration(array $attributes, \DateTimeImmutable $now): ?int
    {
        if (\array_key_exists('max-age', $attributes)) {
            return (int) $attributes['max-age'];
        }

        if (!\array_key_exists('expires', $attributes)) {
            return null;
        }

        $expires = \strtotime($attributes['expires']);

        return $expires === false ? null : $expires - $now->getTimestamp();
    }
}
