<?php

declare(strict_types=1);

namespace BuzzingPixel\RedisCache\DateTime;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use PHPUnit\Framework\TestCase;

use function assert;

class DateTimeUtilityTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testNullValueCreate(): void
    {
        $dateTimeCompare = new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC'),
        );

        $util = new DateTimeUtility();

        $dateTime = $util->createDateTimeImmutable(null);

        self::assertSame(
            $dateTimeCompare->format(DateTimeInterface::ATOM),
            $dateTime->format(DateTimeInterface::ATOM),
        );
    }

    public function testNullValueCreateOrNull(): void
    {
        $util = new DateTimeUtility();

        $dateTime = $util->createDateTimeImmutableOrNull(null);

        self::assertNull($dateTime);
    }

    public function testCreateFromDateTime(): void
    {
        $dateTimeInput = DateTime::createFromFormat(
            DateTimeInterface::ATOM,
            '1982-01-27T10:00:10+00:00'
        );

        assert($dateTimeInput instanceof DateTime);

        $util = new DateTimeUtility();

        $dateTime = $util->createDateTimeImmutableOrNull(
            $dateTimeInput,
        );

        assert($dateTime instanceof DateTimeImmutable);

        self::assertSame(
            '1982-01-27T10:00:10+00:00',
            $dateTime->format(DateTimeInterface::ATOM),
        );
    }

    public function testFromStringAtomFormat(): void
    {
        $util = new DateTimeUtility();

        $dateTime = $util->createDateTimeImmutableOrNull(
            '1992-01-27T10:00:10+00:00',
        );

        assert($dateTime instanceof DateTimeImmutable);

        self::assertSame(
            '1992-01-27T10:00:10+00:00',
            $dateTime->format(DateTimeInterface::ATOM),
        );
    }
}
