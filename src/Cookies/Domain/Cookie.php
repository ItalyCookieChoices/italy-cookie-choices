<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cookies\Domain;

use ItalyCookieChoices\Consent\Domain\Category;

/**
 * A cookie set in the browser of the visitor, as the cookie policy describes it.
 */
final readonly class Cookie
{
    private string $name;

    /**
     * @param string $name The name, or its start followed by * for a prefix, like _ga_*.
     * @param string $provider Who sets it, like Google Analytics or the site.
     * @param string $category The consent category, like "statistics".
     * @param string $purpose What it is used for.
     * @param string $duration How long it is kept, like "2 years" or "Session".
     */
    public function __construct(
        string $name,
        private string $provider,
        private string $category,
        private string $purpose,
        private string $duration,
    ) {
        $this->name = \trim($name);
        if ($this->name === '') {
            throw new \InvalidArgumentException('The cookie needs a name');
        }

        // Throws when the category id is not valid.
        new Category($category);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function purpose(): string
    {
        return $this->purpose;
    }

    public function duration(): string
    {
        return $this->duration;
    }
}
