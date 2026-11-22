<?php

namespace App\Service;

use MongoDB\Collection;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Statistiques de consultation des plats, stockées dans MongoDB (base NoSQL).
 *
 * Un document par plat : { _id: <id du plat dans MySQL>, views: <nombre>, lastViewedAt: <date> }.
 * Les données métier restent dans MySQL, seul le compteur vit dans MongoDB.
 */
class FoodStats
{
    public function __construct(
        private Collection $collection,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Ajoute une consultation au plat. Si MongoDB est indisponible, l'erreur est journalisée
     * mais la réponse du plat n'est pas bloquée : une statistique ne doit jamais casser la carte.
     */
    public function recordView(int $foodId): void
    {
        try {
            $this->collection->updateOne(
                ['_id' => $foodId],
                [
                    '$inc' => ['views' => 1],
                    '$set' => ['lastViewedAt' => new \DateTimeImmutable()],
                ],
                ['upsert' => true],
            );
        } catch (Throwable $e) {
            $this->logger->error('MongoDB indisponible : consultation non comptée.', ['exception' => $e]);
        }
    }

    /**
     * Plats les plus consultés, du plus au moins vu.
     *
     * @return array<int, array{foodId: int, views: int}>
     */
    public function top(int $limit = 5): array
    {
        return self::ranking($this->collection->find([], ['sort' => ['views' => -1], 'limit' => $limit]));
    }

    /**
     * Transforme les documents MongoDB en lignes de classement (l'ordre du curseur est conservé).
     *
     * @param iterable<array|\ArrayAccess> $documents
     *
     * @return array<int, array{foodId: int, views: int}>
     */
    public static function ranking(iterable $documents): array
    {
        $ranking = [];
        foreach ($documents as $document) {
            $ranking[] = ['foodId' => (int) $document['_id'], 'views' => (int) $document['views']];
        }

        return $ranking;
    }
}
