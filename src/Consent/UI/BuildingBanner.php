<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Consent\Domain\Category;

/**
 * Dispatched before the banner is printed, listeners change its texts and the names of the categories:
 *
 * add_action( BuildingBanner::class, static function ( BuildingBanner $event ): void {
 *     $event->changeText( BuildingBanner::REJECT_ALL, 'Only necessary' );
 *     $event->changeCategoryLabel( 'statistics', 'Analytics', 'Anonymous visits.' );
 * } );
 */
final class BuildingBanner
{
    public const TITLE = 'title';
    public const ACCEPT_ALL = 'acceptAll';
    public const REJECT_ALL = 'rejectAll';
    public const PREFERENCES = 'preferences';
    public const CLOSE = 'close';
    public const PREFERENCES_TITLE = 'preferencesTitle';
    public const SAVE = 'save';
    public const CLOSE_PREFERENCES = 'closePreferences';

    /**
     * @param array<string, string> $texts The texts keyed by the constants of this class.
     * @param array<string, array{label: string, description: string}> $categoryLabels Keyed by category id.
     */
    public function __construct(
        private array $texts,
        private array $categoryLabels,
    ) {
    }

    /**
     * @throws \InvalidArgumentException When the text is not one of the constants of this class.
     */
    public function changeText(string $name, string $text): void
    {
        if (!\array_key_exists($name, $this->texts)) {
            throw new \InvalidArgumentException(\sprintf('The banner has no "%s" text', $name));
        }

        $this->texts[$name] = $text;
    }

    public function changeCategoryLabel(string $id, string $label, string $description = ''): void
    {
        $this->categoryLabels[$id] = ['label' => $label, 'description' => $description];
    }

    /**
     * @return array<string, string>
     */
    public function texts(): array
    {
        return $this->texts;
    }

    /**
     * The name and the description of the category, its id when it has no label.
     *
     * @return array{label: string, description: string}
     */
    public function categoryLabel(Category $category): array
    {
        return $this->categoryLabels[$category->id()] ?? ['label' => $category->id(), 'description' => ''];
    }
}
