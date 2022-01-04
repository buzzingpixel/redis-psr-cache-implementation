<?php

declare(strict_types=1);

namespace BuzzingPixel\RedisCache;

use BuzzingPixel\RedisCache\DateTime\DateTimeUtility;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Psr\Cache\CacheItemInterface;

use function time;

// phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint

class CacheItem implements CacheItemInterface
{
    private string $key;

    /** @var mixed */
    private $value;

    private ?DateTimeImmutable $expiresAt;

    private bool $isHit;

    /**
     * @param mixed                         $value
     * @param string|DateTimeInterface|null $expiresAt
     *
     * @throws Exception
     */
    public function __construct(
        string $key,
        $value = null,
        $expiresAt = null,
        bool $isHit = false
    ) {
        $this->key = $key;

        $this->value = $value;

        $this->expiresAt = (new DateTimeUtility())->createDateTimeImmutableOrNull(
            $expiresAt,
        );

        $this->isHit = $isHit;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function set($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param DateTimeInterface|null $expiration
     *
     * @return $this
     *
     * @throws Exception
     */
    public function expiresAt($expiration): self
    {
        $this->expiresAt = (new DateTimeUtility())->createDateTimeImmutableOrNull(
            $expiration,
        );

        return $this;
    }

    /**
     * @param DateInterval|int|null $time
     *
     * @return $this
     *
     * @throws Exception
     */
    public function expiresAfter($time): self
    {
        if ($time === null) {
            $this->expiresAt = null;

            return $this;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $expires = (new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC'),
        ));

        if ($time instanceof DateInterval) {
            $expires = $expires->add($time);

            $this->expiresAt = $expires;

            return $this;
        }

        $expires = $expires->setTimestamp(time() + $time);

        $this->expiresAt = $expires;

        return $this;
    }

    public function expires(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }
}
