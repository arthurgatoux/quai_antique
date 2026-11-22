<?php

namespace App\Service;

use ArrayAccess;
use MongoDB\Client;
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

    /**
     * Le client MongoDB n'est créé qu'au premier usage : une URL ou une configuration invalide
     * ne doit pas empêcher le démarrage de l'application, donc ni les réservations.
     * Le paramètre $collection permet d'injecter une collection déjà prête (tests).
     */
    public function __construct(
        private LoggerInterface $logger,
        private string $mongodbUrl,
        private string $mongodbDatabase,
        private ?Collection $collection = null,
    ) {
    }

    private function collection(): Collection
    {
        // Le délai court évite de bloquer l'API si MongoDB est injoignable.
        return $this->collection ??= (new Client($this->mongodbUrl, [
            'serverSelectionTimeoutMS' => 2000,
            'connectTimeoutMS' => 2000,
        ]))->selectCollection($this->mongodbDatabase, 'booking_stats');
    }

    /**
     * Ajoute une réservation au compteur. Si MongoDB est indisponible, l'erreur est journalisée
     * mais la réservation reste valide : une statistique ne doit jamais empêcher de réserver.
     */
    public function recordBooking(): void
    {
        try {
            $this->collection()->updateOne(
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
        return self::totalFrom($this->collection()->findOne(['_id' => self::DOCUMENT_ID]));
    }

    /** Lit le total dans le document MongoDB (ou 0 si le document n'existe pas encore). */
    public static function totalFrom(array|ArrayAccess|null $document): int
    {
        return (int) ($document['total'] ?? 0);
    }
}
