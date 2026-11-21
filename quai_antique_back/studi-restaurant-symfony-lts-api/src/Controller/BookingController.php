<?php

namespace App\Controller;

use App\Entity\{Booking, Restaurant};
use App\Repository\{BookingRepository, RestaurantRepository};
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/booking', name: 'app_api_booking_')]
class BookingController extends ApiController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private BookingRepository $repository,
        private RestaurantRepository $restaurantRepository,
    ) {
    }

    private function toArray(Booking $booking): array
    {
        $client = $booking->getClient();

        return [
            'id' => $booking->getId(),
            'guestNumber' => $booking->getGuestNumber(),
            'orderDate' => $booking->getOrderDate()->format('Y-m-d'),
            'orderHour' => $booking->getOrderHour()->format('H:i'),
            'allergy' => $booking->getAllergy(),
            'client' => [
                'id' => $client->getId(),
                'firstName' => $client->getFirstName(),
                'lastName' => $client->getLastName(),
            ],
        ];
    }

    /** Un client ne voit et ne modifie que ses réservations ; un admin voit tout. */
    private function canAccess(Booking $booking): bool
    {
        return $this->isGranted('ROLE_ADMIN') || $booking->getClient()->getId() === $this->getUser()->getId();
    }

    private function parse(mixed $value, string $format): ?DateTimeImmutable
    {
        if (!is_string($value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);

        return ($date && $date->format($format) === $value) ? $date : null;
    }

    /**
     * Valide le JSON puis remplit la réservation.
     *
     * @return array{0: string, 1: int}|null message d'erreur et code HTTP, ou null si tout est valide
     */
    private function fill(Booking $booking, array $data, Restaurant $restaurant): ?array
    {
        $guests = $this->intValue($data, 'guestNumber', 1, 255);
        $date = $this->parse($data['orderDate'] ?? null, 'Y-m-d');
        $hour = $this->parse($data['orderHour'] ?? null, 'H:i');

        if ($guests === null || $date === null || $hour === null) {
            return ['Nombre de convives, date (AAAA-MM-JJ) et heure (HH:MM) sont obligatoires.', Response::HTTP_UNPROCESSABLE_ENTITY];
        }

        $allergy = $data['allergy'] ?? null;
        if ($allergy !== null && !is_string($allergy)) {
            return ["Le champ allergies n'est pas valide.", Response::HTTP_UNPROCESSABLE_ENTITY];
        }
        $allergy = $allergy === null ? null : trim($allergy);
        if ($allergy === '') {
            $allergy = null;
        }
        if ($allergy !== null && mb_strlen($allergy) > 255) {
            return ['Les allergies ne doivent pas dépasser 255 caractères.', Response::HTTP_UNPROCESSABLE_ENTITY];
        }

        if ($date < new DateTimeImmutable('today')) {
            return ['La date de réservation est déjà passée.', Response::HTTP_UNPROCESSABLE_ENTITY];
        }

        // Capacité du restaurant pour le service concerné (midi avant 15h, sinon soir)
        $isLunch = $hour < new DateTimeImmutable('1970-01-01 15:00:00');
        $maxGuest = $restaurant->getMaxGuest();
        if ($maxGuest > 0) {
            $alreadyBooked = $this->repository->countGuests($date, $isLunch, $booking->getId());
            if ($alreadyBooked + $guests > $maxGuest) {
                return ['Il ne reste pas assez de places pour ce service. Choisissez un autre créneau.', Response::HTTP_CONFLICT];
            }
        }

        $booking
            ->setGuestNumber($guests)
            ->setOrderDate($date)
            ->setOrderHour($hour)
            ->setAllergy($allergy)
            ->setRestaurant($restaurant);

        return null;
    }

    /** @OA\Get(path="/api/booking", summary="Lister mes réservations (toutes pour un admin)",
     *     @OA\Response(response=200, description="Liste des réservations")) */
    #[Route(methods: 'GET')]
    public function list(): JsonResponse
    {
        $order = ['orderDate' => 'ASC', 'orderHour' => 'ASC'];
        $bookings = $this->isGranted('ROLE_ADMIN')
            ? $this->repository->findBy([], $order)
            : $this->repository->findBy(['client' => $this->getUser()], $order);

        return new JsonResponse(array_map(fn (Booking $booking) => $this->toArray($booking), $bookings));
    }

    /** @OA\Get(path="/api/booking/{id}", summary="Afficher une réservation",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Réservation trouvée"),
     *     @OA\Response(response=404, description="Réservation non trouvée")) */
    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): JsonResponse
    {
        $booking = $this->repository->find($id);
        if (!$booking || !$this->canAccess($booking)) {
            return $this->notFound();
        }

        return new JsonResponse($this->toArray($booking));
    }

    /** @OA\Post(path="/api/booking", summary="Réserver une table",
     *     @OA\RequestBody(required=true, @OA\JsonContent(type="object",
     *         @OA\Property(property="guestNumber", type="integer", example=4),
     *         @OA\Property(property="orderDate", type="string", example="2030-05-12"),
     *         @OA\Property(property="orderHour", type="string", example="19:30"),
     *         @OA\Property(property="allergy", type="string", example="Arachides"))),
     *     @OA\Response(response=201, description="Réservation créée"),
     *     @OA\Response(response=409, description="Plus de places sur ce service")) */
    #[Route(methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $restaurant = $this->restaurantRepository->findOneBy([], ['id' => 'ASC']);
        if (!$restaurant) {
            return $this->fail("Aucun restaurant n'existe en base.", Response::HTTP_CONFLICT);
        }

        $booking = (new Booking())->setClient($this->getUser())->setCreatedAt(new DateTimeImmutable());
        if ($error = $this->fill($booking, $this->body($request), $restaurant)) {
            return $this->fail(...$error);
        }

        $this->manager->persist($booking);
        $this->manager->flush();

        return new JsonResponse($this->toArray($booking), Response::HTTP_CREATED);
    }

    /** @OA\Put(path="/api/booking/{id}", summary="Modifier une réservation",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(type="object",
     *         @OA\Property(property="guestNumber", type="integer"),
     *         @OA\Property(property="orderDate", type="string"),
     *         @OA\Property(property="orderHour", type="string"),
     *         @OA\Property(property="allergy", type="string"))),
     *     @OA\Response(response=204, description="Réservation modifiée"),
     *     @OA\Response(response=404, description="Réservation non trouvée")) */
    #[Route('/{id}', name: 'edit', methods: 'PUT')]
    public function edit(int $id, Request $request): JsonResponse
    {
        $booking = $this->repository->find($id);
        if (!$booking || !$this->canAccess($booking)) {
            return $this->notFound();
        }

        if ($error = $this->fill($booking, $this->body($request), $booking->getRestaurant())) {
            return $this->fail(...$error);
        }

        $booking->setUpdatedAt(new DateTimeImmutable());
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** @OA\Delete(path="/api/booking/{id}", summary="Annuler une réservation",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Réservation annulée"),
     *     @OA\Response(response=404, description="Réservation non trouvée")) */
    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $booking = $this->repository->find($id);
        if (!$booking || !$this->canAccess($booking)) {
            return $this->notFound();
        }

        $this->manager->remove($booking);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
