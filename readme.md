# BuzzingPixel Redis Cache Interface Implementation

## Usage

```php
// Create a Redis instance
$redis = new \Redis();
$redis->connect(getenv('REDIS_HOST'));

// Create the RedisCacheItemPool and send it the redis instance
$cacheItemPool = new \BuzzingPixel\RedisCache\RedisCacheItemPool($redis);
```

And here's an example of configuring the [BuzzingPixel Container](https://github.com/buzzingpixel/container) to use the RedisCacheItemPool by default when auto-wiring the PSR CacheItemPoolInterface (other containers can be configured similarly).

```php
$container = new \BuzzingPixel\Container\Container(
    bindings: [
        \Psr\Cache\CacheItemPoolInterface::class => \BuzzingPixel\RedisCache\RedisCacheItemPool::class,
        \Redis::class => static function (): \Redis {
            $redis = new \Redis();
            $redis->connect(getenv('REDIS_HOST'));
            return $redis;
        }
    ]
);
```
