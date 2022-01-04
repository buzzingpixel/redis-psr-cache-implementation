<?php

declare(strict_types=1);

namespace BuzzingPixel\RedisCache;

use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Throwable;

use function array_map;
use function assert;
use function iterator_to_array;

class CacheItemCollectionTest extends TestCase
{
    public function test(): void
    {
        $item1 = new CacheItem('item1');
        $item2 = new CacheItem('item2');

        $collection = new CacheItemCollection([
            $item1,
            $item2,
        ]);

        self::assertSame(
            [
                $item1,
                $item2,
            ],
            $collection->asArray(),
        );

        self::assertSame(
            'item2',
            /** @phpstan-ignore-next-line */
            $collection->filter(
                static fn (
                    CacheItemInterface $i
                ) => $i->getKey() === 'item2',
            )[0]->getKey()
        );

        self::assertCount(1, $collection->filter(
            static fn (
                CacheItemInterface $i
            ) => $i->getKey() === 'item2',
        ));

        $testItems = array_map(
            static fn (CacheItemInterface $i) => $i,
            iterator_to_array($collection),
        );

        self::assertSame(
            [
                $item1,
                $item2,
            ],
            $testItems,
        );

        self::assertTrue($collection->offsetExists(1));

        self::assertFalse($collection->offsetExists(2));

        $exception1 = null;

        try {
            $collection[0] = $item1;

        /** @phpstan-ignore-next-line */
        } catch (Throwable $e) {
            $exception1 = $e;
        }

        /** @phpstan-ignore-next-line */
        assert($exception1 instanceof LogicException);

        self::assertSame(
            'Cannot add item to immutable collection',
            $exception1->getMessage(),
        );

        $exception2 = null;

        try {
            unset($collection[0]);

        /** @phpstan-ignore-next-line */
        } catch (Throwable $e) {
            $exception2 = $e;
        }

        /** @phpstan-ignore-next-line */
        assert($exception2 instanceof LogicException);

        self::assertSame(
            'Cannot remove item from immutable collection',
            $exception2->getMessage(),
        );
    }
}
