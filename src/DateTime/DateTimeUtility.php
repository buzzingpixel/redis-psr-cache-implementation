<?php

declare(strict_types=1);

namespace BuzzingPixel\RedisCache\DateTime;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;

use function assert;

class DateTimeUtility
{
    /**
     * @param string|DateTimeInterface|null $dateTime
     *
     * @throws Exception
     */
    public function createDateTimeImmutableOrNull($dateTime): ?DateTimeImmutable
    {
        if ($dateTime === null) {
            return null;
        }

        return $this->createDateTimeImmutable($dateTime);
    }

    /**
     * @param string|DateTimeInterface|null $dateTime
     *
     * @throws Exception
     */
    public function createDateTimeImmutable($dateTime): DateTimeImmutable
    {
        if ($dateTime === null) {
            return new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC'),
            );
        }

        if ($dateTime instanceof DateTimeInterface) {
            $class = DateTimeImmutable::createFromFormat(
                DateTimeInterface::ATOM,
                $dateTime->format(DateTimeInterface::ATOM),
            );
        } else {
            $class = DateTimeImmutable::createFromFormat(
                DateTimeInterface::ATOM,
                $dateTime,
            );
        }

        assert($class instanceof DateTimeImmutable);

        return $class->setTimezone(
            new DateTimeZone('UTC')
        );
    }
}
