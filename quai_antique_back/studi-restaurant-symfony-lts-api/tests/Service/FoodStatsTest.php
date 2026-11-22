<?php

namespace App\Tests\Service;

use App\Service\FoodStats;
use MongoDB\Collection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FoodStatsTest extends TestCase
{
    public function testRecordViewIncrementsTheCounterAndCreatesTheDocumentIfMissing(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('updateOne')
            ->with(
                ['_id' => 12],
                $this->callback(static fn (array $update) => $update['$inc'] === ['views' => 1]
                    && $update['$set']['lastViewedAt'] instanceof \DateTimeImmutable),
                ['upsert' => true],
            );

        (new FoodStats($collection, $this->createMock(LoggerInterface::class)))->recordView(12);
    }

    public function testRecordViewDoesNotBreakTheRequestWhenMongoDbIsDown(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('updateOne')->willThrowException(new \RuntimeException('connexion refusée'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        (new FoodStats($collection, $logger))->recordView(1);
    }

    public function testRankingKeepsTheOrderAndCastsTheValues(): void
    {
        $ranking = FoodStats::ranking(new \ArrayIterator([
            ['_id' => 3, 'views' => 42],
            ['_id' => 7, 'views' => '10'],
        ]));

        $this->assertSame([
            ['foodId' => 3, 'views' => 42],
            ['foodId' => 7, 'views' => 10],
        ], $ranking);
    }

    public function testRankingOfNoDocumentIsEmpty(): void
    {
        $this->assertSame([], FoodStats::ranking([]));
    }
}
