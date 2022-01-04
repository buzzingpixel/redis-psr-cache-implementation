<?php

declare(strict_types=1);

namespace BuzzingPixel\RedisCache;

use ArrayAccess;
use Countable;
use Iterator;
use LogicException;
use Psr\Cache\CacheItemInterface;

use function array_filter;
use function array_map;
use function count;

/**
 * @template TValue
 * @implements Iterator<int, TValue>
 * @implements ArrayAccess<int, TValue>
 */
class CacheItemCollection implements ArrayAccess, Countable, Iterator
{
    private int $position = 0;

    /** @var CacheItemInterface[] */
    private array $items = [];

    /**
     * @param iterable<CacheItemInterface> $items
     */
    public function __construct(iterable $items = [])
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    private function addItem(CacheItemInterface $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return CacheItemInterface[]
     */
    public function asArray(): array
    {
        return $this->items;
    }

    /**
     * @return CacheItemCollection<CacheItemInterface>
     */
    public function filter(callable $callback): CacheItemCollection
    {
        return new self(array_filter(
            $this->items,
            $callback,
        ));
    }

    /**
     * @return mixed[]
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->asArray());
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function current(): CacheItemInterface
    {
        return $this->items[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetGet($offset): ?CacheItemInterface
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        throw new LogicException(
            'Cannot add item to immutable collection'
        );
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        throw new LogicException(
            'Cannot remove item from immutable collection'
        );
    }
}
