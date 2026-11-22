<?php

namespace App\Controller;

use App\Service\BookingStats;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\{JsonResponse, Response};
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

#[Route('/api/stats', name: 'app_api_stats_')]
class StatsController extends ApiController
{
    public function __construct(private BookingStats $stats)
    {
    }

    /** @OA\Get(path="/api/stats/bookings", summary="Nombre total de réservations faites (admin)",
     *     @OA\Response(response=200, description="Total des réservations",
     *         @OA\JsonContent(type="object", @OA\Property(property="total", type="integer", example=42))),
     *     @OA\Response(response=403, description="Réservé à l'administrateur"),
     *     @OA\Response(response=503, description="Base MongoDB indisponible")) */
    #[Route('/bookings', name: 'bookings', methods: 'GET')]
    public function bookings(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            return new JsonResponse(['total' => $this->stats->total()]);
        } catch (Throwable) {
            return $this->fail('Statistiques indisponibles.', Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
