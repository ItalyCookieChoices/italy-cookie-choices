<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Domain;

use ItalyCookieChoices\Consent\Domain\Category;

/**
 * A third party that sets cookies, its scripts and frames wait for the consent to its category.
 */
final readonly class Vendor
{
    private const ID_PATTERN = '/^[a-z0-9_-]+$/';

    /**
     * @var list<string>
     */
    private array $sources;

    /**
     * @var list<string>
     */
    private array $signatures;

    /**
     * @param string $category The consent category, like "marketing".
     * @param list<string> $sources The hosts and paths it loads from, like "youtube.com" or "facebook.com/plugins".
     * @param list<string> $signatures Text found only in its inline scripts, like "fbq(".
     */
    public function __construct(
        private string $id,
        private string $category,
        array $sources,
        array $signatures = [],
    ) {
        if (\preg_match(self::ID_PATTERN, $id) !== 1) {
            throw new \InvalidArgumentException(\sprintf(
                'The vendor id "%s" must contain only lowercase letters, numbers, dashes and underscores',
                $id
            ));
        }

        // Throws when the category id is not valid.
        new Category($category);

        $this->sources = $this->nonEmpty($sources);
        $this->signatures = $this->nonEmpty($signatures);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function category(): string
    {
        return $this->category;
    }

    /**
     * Whether the URL is one of its sources, a source matches at the start of the host or of a path segment:
     * "youtube.com" matches www.youtube.com and youtube.com, not notyoutube.com.
     */
    public function loads(string $url): bool
    {
        $url = \strtolower((string) \preg_replace('#^([a-z][a-z0-9+.-]*:)?//#i', '', \trim($url)));

        foreach ($this->sources as $source) {
            if ($this->startsASegment($url, \strtolower($source))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the inline script contains one of its signatures.
     */
    public function runs(string $script): bool
    {
        foreach ($this->signatures as $signature) {
            if (\str_contains($script, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function startsASegment(string $url, string $source): bool
    {
        $offset = 0;
        while (($position = \strpos($url, $source, $offset)) !== false) {
            if ($position === 0 || \in_array($url[$position - 1], ['.', '/'], true)) {
                return true;
            }

            $offset = $position + 1;
        }

        return false;
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private function nonEmpty(array $values): array
    {
        return \array_values(\array_filter(
            \array_map('trim', $values),
            static fn(string $value): bool => $value !== ''
        ));
    }
}
