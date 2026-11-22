<?php

namespace App\Tests\Service;

use App\Service\BookingStats;
use MongoDB\Collection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BookingStatsTest extends TestCase
{
    private function stats(Collection $collection): BookingStats
    {
        return new BookingStats($this->createMock(LoggerInterface::class), 'mongodb://127.0.0.1', 'test', $collection);
    }

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

        $this->stats($collection)->recordBooking();
    }

    public function testRecordBookingDoesNotBreakTheReservationWhenMongoDbIsDown(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('updateOne')->willThrowException(new \RuntimeException('connexion refusée'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        (new BookingStats($logger, 'mongodb://127.0.0.1', 'test', $collection))->recordBooking();
    }

    public function testTotalReadsTheCounterFromTheDocument(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('findOne')
            ->with(['_id' => 'bookings'])
            ->willReturn(['_id' => 'bookings', 'total' => 42]);

        $this->assertSame(42, $this->stats($collection)->total());
    }

    public function testTotalIsZeroBeforeTheFirstBooking(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('findOne')->willReturn(null);

        $this->assertSame(0, $this->stats($collection)->total());
    }

    public function testAnInvalidMongoDbUrlDoesNotBreakTheReservation(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        (new BookingStats($logger, 'ceci-n-est-pas-une-url', 'test'))->recordBooking();
    }
}
