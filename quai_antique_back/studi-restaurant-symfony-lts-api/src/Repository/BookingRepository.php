<?php

namespace App\Repository;

use App\Entity\Booking;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * Nombre de convives déjà réservés pour un service (midi ou soir) d'une date donnée.
     * Le service du midi s'arrête avant 15h.
     */
    public function countGuests(DateTimeImmutable $date, bool $isLunch, ?int $excludeId = null): int
    {
        $limit = new DateTimeImmutable('1970-01-01 15:00:00');

        $qb = $this->createQueryBuilder('b')
            ->select('COALESCE(SUM(b.guestNumber), 0)')
            ->where('b.orderDate = :date')
            ->andWhere($isLunch ? 'b.orderHour < :limit' : 'b.orderHour >= :limit')
            ->setParameter('date', $date, Types::DATE_IMMUTABLE)
            ->setParameter('limit', $limit, Types::DATETIME_IMMUTABLE);

        if ($excludeId !== null) {
            $qb->andWhere('b.id != :id')->setParameter('id', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
