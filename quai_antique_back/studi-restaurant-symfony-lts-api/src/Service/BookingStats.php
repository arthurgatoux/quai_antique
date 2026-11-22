<?php

namespace App\Service;

use ArrayAccess;
use MongoDB\Collection;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Compteur de réservations, stocké dans MongoDB (base NoSQL).
 *
 * Un seul document : { _id: "bookings", total: <nombre de réservations faites>, lastBookingAt: <date> }.
 * Le compteur ne baisse jamais : il compte les réservations créées, pas celles encore actives.
 * Les réservations elles-mêmes restent dans MySQL.
 */
class BookingStats
{
    private const DOCUMENT_ID = 'bookings';

    public function __construct(
        private Collection $collection,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Ajoute une réservation au compteur. Si MongoDB est indisponible, l'erreur est journalisée
     * mais la réservation reste valide : une statistique ne doit jamais empêcher de réserver.
     */
    public function recordBooking(): void
    {
        try {
            $this->collection->updateOne(
                ['_id' => self::DOCUMENT_ID],
                [
                    '$inc' => ['total' => 1],
                    '$set' => ['lastBookingAt' => new \DateTimeImmutable()],
                ],
                ['upsert' => true],
            );
        } catch (Throwable $e) {
            $this->logger->error('MongoDB indisponible : réservation non comptée.', ['exception' => $e]);
        }
    }

    /** Nombre total de réservations faites (0 tant qu'aucune n'a été comptée). */
    public function total(): int
    {
        return self::totalFrom($this->collection->findOne(['_id' => self::DOCUMENT_ID]));
    }

    /** Lit le total dans le document MongoDB (ou 0 si le document n'existe pas encore). */
    public static function totalFrom(array|ArrayAccess|null $document): int
    {
        return (int) ($document['total'] ?? 0);
    }
}
