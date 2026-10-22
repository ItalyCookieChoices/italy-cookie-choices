<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Infrastructure;

use ItalyCookieChoices\Consent\Domain\Categories;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\Domain\ConsentDuration;
use ItalyCookieChoices\Consent\Domain\ConsentPolicy;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;

/**
 * Builds the consent policy, themes and plugins change it with filters.
 *
 * It is built when it is needed, after the themes have added their filters.
 */
final readonly class FilteredConsentPolicy
{
    public const CATEGORIES_FILTER = 'italy_cookie_choices_categories';
    public const VERSION_FILTER = 'italy_cookie_choices_policy_version';
    public const DURATION_FILTER = 'italy_cookie_choices_consent_duration';

    public const DEFAULT_VERSION = '1';

    public function create(): ConsentPolicy
    {
        return new ConsentPolicy(
            $this->categories(),
            $this->version(),
            $this->duration()
        );
    }

    private function categories(): Categories
    {
        /**
         * Filters the consent categories, in the order they are shown.
         *
         * The necessary category is always present and required.
         *
         * @param mixed $categories A list of categories, like ['id' => 'statistics', 'required' => false].
         */
        $filtered = \apply_filters(self::CATEGORIES_FILTER, [
            ['id' => Category::NECESSARY, 'required' => true],
            ['id' => Category::PREFERENCES],
            ['id' => Category::STATISTICS],
            ['id' => Category::MARKETING],
        ]);

        $categories = [Category::NECESSARY => new Category(Category::NECESSARY, true)];
        foreach (\is_array($filtered) ? $filtered : [] as $definition) {
            $category = $this->categoryFrom($definition);
            if ($category === null || \array_key_exists($category->id(), $categories)) {
                continue;
            }

            $categories[$category->id()] = $category;
        }

        return new Categories(...\array_values($categories));
    }

    private function categoryFrom(mixed $definition): ?Category
    {
        if (!\is_array($definition) || !\is_string($definition['id'] ?? null)) {
            return null;
        }

        try {
            return new Category($definition['id'], ($definition['required'] ?? false) === true);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function version(): PolicyVersion
    {
        /**
         * Filters the cookie policy version, change it to ask the consent again to every visitor.
         *
         * @param mixed $version The version, a string or a number.
         */
        $version = \apply_filters(self::VERSION_FILTER, self::DEFAULT_VERSION);

        return new PolicyVersion(
            \is_scalar($version) && \trim((string) $version) !== '' ? (string) $version : self::DEFAULT_VERSION
        );
    }

    private function duration(): ConsentDuration
    {
        /**
         * Filters how many days a consent lasts, six months by default.
         *
         * @param mixed $days The number of days.
         */
        $days = \apply_filters(self::DURATION_FILTER, ConsentDuration::DEFAULT_DAYS);

        return new ConsentDuration(
            \is_int($days) && $days > 0 ? $days : ConsentDuration::DEFAULT_DAYS
        );
    }
}
