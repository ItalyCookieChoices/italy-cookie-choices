<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Domain;

/**
 * The categories the visitor chooses from, in the order they are shown.
 *
 * @implements \IteratorAggregate<int, Category>
 */
final readonly class Categories implements \IteratorAggregate
{
    /**
     * @var list<Category>
     */
    private array $categories;

    public function __construct(Category ...$categories)
    {
        $ids = \array_map(static fn(Category $category): string => $category->id(), $categories);
        if (\count($ids) !== \count(\array_unique($ids))) {
            throw new \InvalidArgumentException('The category ids must be unique');
        }

        $this->categories = \array_values($categories);
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return \array_map(static fn(Category $category): string => $category->id(), $this->categories);
    }

    /**
     * @return list<string>
     */
    public function requiredIds(): array
    {
        $required = \array_filter($this->categories, static fn(Category $category): bool => $category->isRequired());

        return \array_values(\array_map(static fn(Category $category): string => $category->id(), $required));
    }

    public function has(string $id): bool
    {
        return \in_array($id, $this->ids(), true);
    }

    /**
     * @return \ArrayIterator<int, Category>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->categories);
    }
}
