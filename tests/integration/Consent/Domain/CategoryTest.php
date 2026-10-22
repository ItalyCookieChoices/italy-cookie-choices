<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\Domain;

use ItalyCookieChoices\Consent\Domain\Categories;
use ItalyCookieChoices\Consent\Domain\Category;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CategoryTest extends WPTestCase
{
    public function testACategoryIsOptionalByDefault(): void
    {
        $category = new Category('statistics');

        $this->assertSame('statistics', $category->id());
        $this->assertFalse($category->isRequired());
    }

    public function testACategoryCanBeRequired(): void
    {
        $this->assertTrue((new Category('necessary', true))->isRequired());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function invalidIds(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Statistics'];
        yield 'space' => ['social media'];
        yield 'quote' => ['a"b'];
    }

    /**
     * @dataProvider invalidIds
     */
    public function testTheIdMustBeASlug(string $id): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Category($id);
    }

    public function testTheValidIdsAccepted(): void
    {
        $this->assertSame('social_media-2', (new Category('social_media-2'))->id());
    }

    public function testCategoriesKeepTheirOrder(): void
    {
        $categories = new Categories(
            new Category('necessary', true),
            new Category('marketing'),
            new Category('statistics')
        );

        $this->assertSame(['necessary', 'marketing', 'statistics'], $categories->ids());
        $this->assertSame(['necessary'], $categories->requiredIds());
        $this->assertTrue($categories->has('marketing'));
        $this->assertFalse($categories->has('preferences'));
        $this->assertCount(3, \iterator_to_array($categories));
    }

    public function testTheRequiredIdsAreAList(): void
    {
        $categories = new Categories(new Category('marketing'), new Category('necessary', true));

        $this->assertSame(['necessary'], $categories->requiredIds());
    }

    public function testCategoriesMustBeUnique(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Categories(new Category('marketing'), new Category('marketing'));
    }
}
