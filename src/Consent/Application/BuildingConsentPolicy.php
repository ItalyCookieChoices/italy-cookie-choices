<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Application;

use ItalyCookieChoices\Consent\Domain\Categories;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\Domain\ConsentDuration;
use ItalyCookieChoices\Consent\Domain\ConsentPolicy;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;

/**
 * Dispatched while the consent policy is built, listeners change the categories, the version and the duration:
 *
 * add_action( BuildingConsentPolicy::class, static function ( BuildingConsentPolicy $event ): void {
 *     $event->changeVersion( new PolicyVersion( '2' ) );
 * } );
 *
 * The necessary category is always present and required.
 */
final class BuildingConsentPolicy
{
    /**
     * @var array<string, Category>
     */
    private array $categories = [];

    public function __construct(
        Categories $categories,
        private PolicyVersion $version,
        private ConsentDuration $duration,
    ) {
        foreach ($categories as $category) {
            $this->addCategory($category);
        }
    }

    /**
     * Adds the category at the end, or replaces the one with the same id in its place.
     */
    public function addCategory(Category $category): void
    {
        if ($category->id() === Category::NECESSARY) {
            $this->categories[Category::NECESSARY] = new Category(Category::NECESSARY, true);
            return;
        }

        $this->categories[$category->id()] = $category;
    }

    public function removeCategory(string $id): void
    {
        if ($id === Category::NECESSARY) {
            return;
        }

        unset($this->categories[$id]);
    }

    public function changeVersion(PolicyVersion $version): void
    {
        $this->version = $version;
    }

    public function changeDuration(ConsentDuration $duration): void
    {
        $this->duration = $duration;
    }

    public function categories(): Categories
    {
        $categories = $this->categories;
        $necessary = $categories[Category::NECESSARY] ?? new Category(Category::NECESSARY, true);
        unset($categories[Category::NECESSARY]);

        return new Categories($necessary, ...\array_values($categories));
    }

    public function version(): PolicyVersion
    {
        return $this->version;
    }

    public function duration(): ConsentDuration
    {
        return $this->duration;
    }

    public function policy(): ConsentPolicy
    {
        return new ConsentPolicy($this->categories(), $this->version, $this->duration);
    }
}
