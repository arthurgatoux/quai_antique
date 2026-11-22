<?php

namespace App\Tests\Service;

use App\Service\BookingStats;
use MongoDB\Collection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BookingStatsTest extends TestCase
{
    public function testRecordBookingIncrementsTheCounterAndCreatesTheDocumentIfMissing(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('updateOne')
            ->with(
                ['_id' => 'bookings'],
                $this->callback(static fn (array $update) => $update['$inc'] === ['total' => 1]
                    && $update['$set']['lastBookingAt'] instanceof \DateTimeImmutable),
                ['upsert' => true],
            );

        (new BookingStats($collection, $this->createMock(LoggerInterface::class)))->recordBooking();
    }

    public function testRecordBookingDoesNotBreakTheReservationWhenMongoDbIsDown(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('updateOne')->willThrowException(new \RuntimeException('connexion refusée'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        (new BookingStats($collection, $logger))->recordBooking();
    }

    public function testTotalReadsTheCounterFromTheDocument(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('findOne')
            ->with(['_id' => 'bookings'])
            ->willReturn(['_id' => 'bookings', 'total' => 42]);

        $this->assertSame(42, (new BookingStats($collection, $this->createMock(LoggerInterface::class)))->total());
    }

    public function testTotalIsZeroBeforeTheFirstBooking(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('findOne')->willReturn(null);

        $this->assertSame(0, (new BookingStats($collection, $this->createMock(LoggerInterface::class)))->total());
    }
}
