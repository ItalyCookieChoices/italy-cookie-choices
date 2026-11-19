<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\Domain;

use ItalyCookieChoices\Consent\Domain\Category;

/**
 * Code the site owner adds to the pages, run after the consent to its category.
 *
 * The code is HTML: scripts, and the elements they need, like the image of a tracking pixel.
 */
final readonly class CustomScript
{
    public const HEAD = 'head';
    public const BODY = 'body';

    private const POSITIONS = [self::HEAD, self::BODY];

    private string $code;

    /**
     * @param string $name What the code is, like "Chat".
     * @param string $category The consent category, like "statistics".
     * @param string $position Where the code goes: in the head or at the end of the body.
     * @param string $code The HTML of the code.
     */
    public function __construct(
        private string $name,
        private string $category,
        private string $position,
        string $code,
    ) {
        $this->code = \trim($code);
        if ($this->code === '') {
            throw new \InvalidArgumentException('The custom script needs its code');
        }

        if (!\in_array($position, self::POSITIONS, true)) {
            throw new \InvalidArgumentException(\sprintf('The position "%s" is not head or body', $position));
        }

        // Throws when the category id is not valid.
        new Category($category);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function position(): string
    {
        return $this->position;
    }

    public function code(): string
    {
        return $this->code;
    }

    /**
     * The code of the necessary category runs on every page, it needs no consent.
     */
    public function runsWithoutConsent(): bool
    {
        return $this->category === Category::NECESSARY;
    }
}
