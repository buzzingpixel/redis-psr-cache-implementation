<?php

declare(strict_types=1);

namespace BuzzingPixel\RedisCache;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Redis;

use function array_map;
use function array_walk;
use function assert;
use function serialize;
use function unserialize;

// phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint

class RedisCacheItemPool implements CacheItemPoolInterface
{
    private Redis $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param string $key
     *
     * @throws Exception
     */
    public function getItem($key): CacheItemInterface
    {
        $redisItem = $this->redis->get($key);

        if ($redisItem === false) {
            return new CacheItem($key);
        }

        $ttl = $this->redis->ttl($key);

        return new CacheItem(
            $key,
            unserialize($redisItem),
            $ttl > 0 ? (new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC')
            ))->add(new DateInterval(
                'PT' . $ttl . 'S'
            )) : null,
            true,
        );
    }

    /**
     * @return CacheItemCollection<CacheItemInterface>
     *
     * @throws Exception
     *
     * @inheritDoc
     */
    public function getItems(array $keys = []): iterable
    {
        return new CacheItemCollection(array_map(
            fn (string $k) => $this->getItem($k),
            $keys,
        ));
    }

    /**
     * @param string $key
     */
    public function hasItem($key): bool
    {
        return (bool) $this->redis->exists($key);
    }

    public function clear(): bool
    {
        return $this->redis->flushAll();
    }

    /**
     * @param string $key
     */
    public function deleteItem($key): bool
    {
        return $this->redis->del($key) === 1;
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys): bool
    {
        return $this->redis->del($keys) > 0;
    }

    public function save(CacheItemInterface $item): bool
    {
        assert($item instanceof CacheItem);

        $expires = $item->expires();

        $value = serialize($item->get());

        if ($expires !== null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $currentTime = new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC')
            );

            /** @psalm-suppress MixedReturnStatement */
            return $this->redis->setex(
                $item->getKey(),
                $expires->getTimestamp() - $currentTime->getTimestamp(),
                $value,
            );
        }

        /** @psalm-suppress MixedReturnStatement */
        return $this->redis->set(
            $item->getKey(),
            $value,
        );
    }

    /** @var CacheItemInterface[] */
    private array $deferred = [];

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[] = $item;

        return true;
    }

    public function commit(): bool
    {
        array_walk(
            $this->deferred,
            fn (CacheItemInterface $i) => $this->save($i),
        );

        $this->deferred = [];

        return true;
    }
}
