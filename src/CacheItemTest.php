<?php

declare(strict_types=1);

namespace BuzzingPixel\RedisCache;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

use function time;

class CacheItemTest extends TestCase
{
    public function test(): void
    {
        $item = new CacheItem(
            'fooKey',
            'fooValue',
            '1982-01-27T10:00:10+00:00',
            true,
        );

        self::assertSame('fooKey', $item->getKey());

        self::assertSame('fooValue', $item->get());

        self::assertTrue($item->isHit());

        self::assertSame(
            'fooNewValue',
            $item->set('fooNewValue')->get(),
        );

        self::assertSame('fooNewValue', $item->get());

        self::assertSame(
            '1982-01-27T10:00:10+00:00',
            /** @phpstan-ignore-next-line */
            $item->expires()->format(DateTimeInterface::ATOM),
        );

        $expiresAt = DateTime::createFromFormat(
            DateTimeInterface::ATOM,
            '1983-01-27T10:00:10+00:00',
        );

        self::assertSame(
            '1983-01-27T10:00:10+00:00',
            /** @phpstan-ignore-next-line */
            $item->expiresAt($expiresAt)->expires()->format(
                DateTimeInterface::ATOM,
            ),
        );

        self::assertSame(
            '1983-01-27T10:00:10+00:00',
            /** @phpstan-ignore-next-line */
            $item->expires()->format(DateTimeInterface::ATOM),
        );

        $now = (new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        ));

        self::assertSame(
            $now->add(new DateInterval('P1Y'))->format(
                DateTimeInterface::ATOM,
            ),
            /** @phpstan-ignore-next-line */
            $item->expiresAfter(
                new DateInterval('P1Y')
            )->expires()->format(DateTimeInterface::ATOM),
        );

        self::assertSame(
            $now->add(new DateInterval('P1Y'))->format(
                DateTimeInterface::ATOM,
            ),
            /** @phpstan-ignore-next-line */
            $item->expires()->format(DateTimeInterface::ATOM),
        );

        self::assertSame(
            $now->setTimestamp(time() + 500)->format(
                DateTimeInterface::ATOM,
            ),
            /** @phpstan-ignore-next-line */
            $item->expiresAfter(500)->expires()->format(
                DateTimeInterface::ATOM,
            ),
        );

        self::assertSame(
            $now->setTimestamp(time() + 500)->format(
                DateTimeInterface::ATOM,
            ),
            /** @phpstan-ignore-next-line */
            $item->expires()->format(DateTimeInterface::ATOM),
        );

        self::assertNull($item->expiresAfter(null)->expires());

        self::assertNull($item->expires());
    }
}
