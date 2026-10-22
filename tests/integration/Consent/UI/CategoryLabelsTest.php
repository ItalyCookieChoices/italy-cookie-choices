<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\UI;

use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\UI\CategoryLabels;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CategoryLabelsTest extends WPTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public function knownCategories(): iterable
    {
        yield 'necessary' => ['necessary', 'Necessary'];
        yield 'preferences' => ['preferences', 'Preferences'];
        yield 'statistics' => ['statistics', 'Statistics'];
        yield 'marketing' => ['marketing', 'Marketing'];
    }

    /**
     * @dataProvider knownCategories
     */
    public function testTheKnownCategoriesHaveALabelAndADescription(string $id, string $label): void
    {
        $labels = (new CategoryLabels())->for(new Category($id));

        $this->assertSame($label, $labels['label']);
        $this->assertNotSame('', $labels['description']);
    }

    public function testAnUnknownCategoryUsesItsId(): void
    {
        $this->assertSame(
            ['label' => 'social', 'description' => ''],
            (new CategoryLabels())->for(new Category('social'))
        );
    }

    public function testAFilterThatReturnsNoArrayUsesTheIds(): void
    {
        \add_filter(CategoryLabels::FILTER, '__return_null');

        $this->assertSame(
            ['label' => 'statistics', 'description' => ''],
            (new CategoryLabels())->for(new Category('statistics'))
        );
    }
}
