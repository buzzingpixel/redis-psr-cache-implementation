<?php

declare(strict_types=1);

namespace BuzzingPixel\RedisCache;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Redis;

use function serialize;

class RedisCacheItemPoolTest extends TestCase
{
    /** @var mixed[] */
    private array $calls = [];

    /** @var mixed */
    private $redisGetValue = false;

    private int $redisTtlValue = 0;

    private int $redisExistsValue = 0;

    private RedisCacheItemPool $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calls = [];

        $this->redisGetValue = false;

        $this->redisTtlValue = 0;

        $this->redisExistsValue = 0;

        $this->cache = new RedisCacheItemPool($this->mockRedis());
    }

    private function mockRedis(): Redis
    {
        $mock = $this->createMock(Redis::class);

        $mock->method('get')->willReturnCallback(
            function (string $key) {
                $this->calls[] = [
                    'object' => 'Redis',
                    'method' => 'get',
                    'key' => $key,
                ];

                return $this->redisGetValue;
            }
        );

        $mock->method('ttl')->willReturnCallback(
            function (string $key): int {
                $this->calls[] = [
                    'object' => 'Redis',
                    'method' => 'ttl',
                    'key' => $key,
                ];

                return $this->redisTtlValue;
            }
        );

        $mock->method('exists')->willReturnCallback(
            function (string $key): int {
                $this->calls[] = [
                    'object' => 'Redis',
                    'method' => 'exists',
                    'key' => $key,
                ];

                return $this->redisExistsValue;
            }
        );

        $mock->method('flushAll')->willReturnCallback(
            function (): bool {
                $this->calls[] = [
                    'object' => 'Redis',
                    'method' => 'flushAll',
                ];

                return true;
            }
        );

        $mock->method('del')->willReturnCallback(
            function ($keys): int {
                $this->calls[] = [
                    'object' => 'Redis',
                    'method' => 'del',
                    'keys' => $keys,
                ];

                return 1;
            }
        );

        $mock->method('setex')->willReturnCallback(
            function (string $key, int $ttl, $val): bool {
                $this->calls[] = [
                    'object' => 'Redis',
                    'method' => 'setex',
                    'key' => $key,
                    'ttl' => $ttl,
                    'val' => $val,
                ];

                return true;
            }
        );

        $mock->method('set')->willReturnCallback(
            function (string $key, $val): bool {
                $this->calls[] = [
                    'object' => 'Redis',
                    'method' => 'set',
                    'key' => $key,
                    'val' => $val,
                ];

                return true;
            }
        );

        return $mock;
    }

    /**
     * @throws Exception
     */
    public function testGetItemWhenRedisDoesNotHaveItem(): void
    {
        $cacheItem = $this->cache->getItem('fooBarItem');

        self::assertSame(
            [
                [
                    'object' => 'Redis',
                    'method' => 'get',
                    'key' => 'fooBarItem',
                ],
            ],
            $this->calls,
        );

        self::assertSame(
            'fooBarItem',
            $cacheItem->getKey(),
        );

        self::assertNull($cacheItem->get());

        self::assertFalse($cacheItem->isHit());

        /** @phpstan-ignore-next-line */
        self::assertNull($cacheItem->expires());
    }

    /**
     * @throws Exception
     */
    public function testGetItemWhenRedisHasItemAndNoTtl(): void
    {
        $this->redisGetValue = serialize('fooBarString');

        $cacheItem = $this->cache->getItem('fooBarItem');

        self::assertSame(
            [
                [
                    'object' => 'Redis',
                    'method' => 'get',
                    'key' => 'fooBarItem',
                ],
                [
                    'object' => 'Redis',
                    'method' => 'ttl',
                    'key' => 'fooBarItem',
                ],
            ],
            $this->calls,
        );

        self::assertSame(
            'fooBarItem',
            $cacheItem->getKey(),
        );

        self::assertSame('fooBarString', $cacheItem->get());

        self::assertTrue($cacheItem->isHit());

        /** @phpstan-ignore-next-line */
        self::assertNull($cacheItem->expires());
    }

    /**
     * @throws Exception
     */
    public function testGetItemWhenRedisHasItemAndHasTtl(): void
    {
        $this->redisGetValue = serialize([
            'fooBarString',
            'fooBarStuff',
        ]);

        $this->redisTtlValue = 456;

        $cacheItem = $this->cache->getItem('barBazItem');

        self::assertSame(
            [
                [
                    'object' => 'Redis',
                    'method' => 'get',
                    'key' => 'barBazItem',
                ],
                [
                    'object' => 'Redis',
                    'method' => 'ttl',
                    'key' => 'barBazItem',
                ],
            ],
            $this->calls,
        );

        self::assertSame(
            'barBazItem',
            $cacheItem->getKey(),
        );

        self::assertSame(
            [
                'fooBarString',
                'fooBarStuff',
            ],
            $cacheItem->get(),
        );

        self::assertTrue($cacheItem->isHit());

        $expires = (new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        ))->add(new DateInterval(
            'PT456S'
        ));

        self::assertSame(
            $expires->format(DateTimeInterface::ATOM),
            /** @phpstan-ignore-next-line */
            $cacheItem->expires()->format(
                DateTimeInterface::ATOM,
            ),
        );
    }

    /**
     * @throws InvalidArgumentException
     *
     * @phpstan-ignore-next-line
     */
    public function testGetItemsWhenNoItems(): void
    {
        $collection = $this->cache->getItems([
            'key1',
            'key2',
        ]);

        self::assertSame(
            [
                [
                    'object' => 'Redis',
                    'method' => 'get',
                    'key' => 'key1',
                ],
                [
                    'object' => 'Redis',
                    'method' => 'get',
                    'key' => 'key2',
                ],
            ],
            $this->calls,
        );

        self::assertSame(
            [
                0 =>
                    [
                        'key' => 'key1',
                        'value' => null,
                        'isHit' => false,
                    ],
                1 =>
                    [
                        'key' => 'key2',
                        'value' => null,
                        'isHit' => false,
                    ],
            ],
            $collection->map(
                static fn (CacheItemInterface $item) => [
                    'key' => $item->getKey(),
                    'value' => $item->get(),
                    'isHit' => $item->isHit(),
                ],
            )
        );
    }

    public function testExistsWhenFalse(): void
    {
        self::assertFalse($this->cache->hasItem('fooBar'));

        self::assertSame(
            [
                [
                    'object' => 'Redis',
                    'method' => 'exists',
                    'key' => 'fooBar',
                ],
            ],
            $this->calls,
        );
    }

    public function testExistsWhenTrue(): void
    {
        $this->redisExistsValue = 34;

        self::assertTrue($this->cache->hasItem('fooBar'));

        self::assertSame(
            [
                [
                    'object' => 'Redis',
                    'method' => 'exists',
                    'key' => 'fooBar',
                ],
            ],
            $this->calls,
        );
    }

    public function testClear(): void
    {
        self::assertTrue($this->cache->clear());

        self::assertSame(
            [
                [
                    'object' => 'Redis',
                    'method' => 'flushAll',
                ],
            ],
            $this->calls,
        );
    }

    public function testDeleteItem(): void
    {
        self::assertTrue($this->cache->deleteItem('fooKey'));

        self::assertSame(
            [
                [
                    'object' => 'Redis',
                    'method' => 'del',
                    'keys' => 'fooKey',
                ],
            ],
            $this->calls,
        );
    }

    /**
     * @throws InvalidArgumentException
     *
     * @phpstan-ignore-next-line
     */
    public function testDeleteItems(): void
    {
        self::assertTrue($this->cache->deleteItems([
            'fooKey1',
            'fooKey2',
        ]));

        self::assertSame(
            [
                [
                    'object' => 'Redis',
                    'method' => 'del',
                    'keys' => [
                        'fooKey1',
                        'fooKey2',
                    ],
                ],
            ],
            $this->calls,
        );
    }

    /**
     * @throws Exception
     */
    public function testSaveWhenExpiresIsNotNull(): void
    {
        $currentTime = new DateTime(
            'now',
            new DateTimeZone('UTC'),
        );

        $expiresAt = (new DateTime())->add(new DateInterval('P1Y'));

        $item = new CacheItem(
            'fooBarKey',
            'fooBarValue',
            $expiresAt,
        );

        self::assertTrue($this->cache->save($item));

        self::assertSame(
            [
                [
                    'object' => 'Redis',
                    'method' => 'setex',
                    'key' => 'fooBarKey',
                    'ttl' => $expiresAt->getTimestamp() - $currentTime->getTimestamp(),
                    'val' => 's:11:"fooBarValue";',
                ],
            ],
            $this->calls,
        );
    }

    public function testSaveWhenExpiresIsNull(): void
    {
        $item = new CacheItem(
            'fooBarKey',
            'fooBarValue',
        );

        self::assertTrue($this->cache->save($item));

        self::assertSame(
            [
                [
                    'object' => 'Redis',
                    'method' => 'set',
                    'key' => 'fooBarKey',
                    'val' => 's:11:"fooBarValue";',
                ],
            ],
            $this->calls,
        );
    }

    public function testDeferred(): void
    {
        self::assertTrue($this->cache->commit());

        self::assertSame([], $this->calls);

        $item1 = new CacheItem(
            'fooBarKey1',
            'fooBarValue1',
        );

        $item2 = new CacheItem(
            'fooBarKey2',
            'fooBarValue2',
        );

        $this->cache->saveDeferred($item1);

        $this->cache->saveDeferred($item2);

        $this->cache->commit();

        $this->cache->commit();

        self::assertSame(
            [
                [
                    'object' => 'Redis',
                    'method' => 'set',
                    'key' => 'fooBarKey1',
                    'val' => 's:12:"fooBarValue1";',
                ],
                [
                    'object' => 'Redis',
                    'method' => 'set',
                    'key' => 'fooBarKey2',
                    'val' => 's:12:"fooBarValue2";',
                ],
            ],
            $this->calls,
        );
    }
}
