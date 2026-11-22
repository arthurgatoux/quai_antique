<?php

namespace App\Controller;

use App\Repository\FoodRepository;
use App\Service\FoodStats;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

#[Route('/api/stats', name: 'app_api_stats_')]
class StatsController extends ApiController
{
    public function __construct(
        private FoodStats $stats,
        private FoodRepository $foodRepository,
    ) {
    }

    /** @OA\Get(path="/api/stats/food", summary="Plats les plus consultés (admin)",
     *     @OA\Parameter(name="limit", in="query", description="Nombre de plats (1 à 20, 5 par défaut)", @OA\Schema(type="integer", default=5)),
     *     @OA\Response(response=200, description="Classement des plats par nombre de consultations",
     *         @OA\JsonContent(type="array", @OA\Items(type="object",
     *             @OA\Property(property="foodId", type="integer", example=3),
     *             @OA\Property(property="views", type="integer", example=42),
     *             @OA\Property(property="title", type="string", example="Tartare de saumon")))),
     *     @OA\Response(response=403, description="Réservé à l'administrateur"),
     *     @OA\Response(response=503, description="Base MongoDB indisponible")) */
    #[Route('/food', name: 'food', methods: 'GET')]
    public function food(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $limit = $this->intValue($request->query->all(), 'limit', 1, 20) ?? 5;

        try {
            $top = $this->stats->top($limit);
        } catch (Throwable) {
            return $this->fail('Statistiques indisponibles.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if ($top === []) {
            return new JsonResponse([]);
        }

        // Les statistiques viennent de MongoDB, les titres des plats de MySQL.
        $titles = [];
        foreach ($this->foodRepository->findBy(['id' => array_column($top, 'foodId')]) as $food) {
            $titles[$food->getId()] = $food->getTitle();
        }

        return new JsonResponse(array_map(
            static fn (array $row) => $row + ['title' => $titles[$row['foodId']] ?? null],
            $top,
        ));
    }
}
