<?php

namespace App\Controller;

use App\Entity\Food;
use App\Repository\{CategoryRepository, FoodRepository};
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/food', name: 'app_api_food_')]
class FoodController extends ApiController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private FoodRepository $repository,
        private CategoryRepository $categoryRepository,
    ) {
    }

    private function toArray(Food $food): array
    {
        return [
            'id' => $food->getId(),
            'title' => $food->getTitle(),
            'description' => $food->getDescription(),
            'price' => $food->getPrice(),
            'categories' => array_map(static fn ($category) => [
                'id' => $category->getId(),
                'title' => $category->getTitle(),
            ], $food->getCategories()->toArray()),
        ];
    }

    /** Remplit le plat depuis le JSON. Retourne un message d'erreur, ou null si tout est valide. */
    private function fill(Food $food, array $data): ?string
    {
        $title = $this->text($data, 'title', 64);
        // La description peut être vide (ex. une boisson), mais doit être présente et raisonnable.
        $description = isset($data['description']) && is_string($data['description']) ? trim($data['description']) : null;
        if ($description !== null && mb_strlen($description) > 5000) {
            $description = null;
        }
        $price = $this->intValue($data, 'price', 0, 32767);

        if ($title === null || $description === null || $price === null) {
            return 'Titre (64 caractères max), description (5000 max) et prix (entier positif) sont obligatoires.';
        }

        $food->setTitle($title)->setDescription($description)->setPrice($price);

        foreach ($food->getCategories()->toArray() as $category) {
            $food->removeCategory($category);
        }
        foreach ($this->idList($data, 'categories') as $categoryId) {
            $category = $this->categoryRepository->find($categoryId);
            if (!$category) {
                return "La catégorie $categoryId n'existe pas.";
            }
            $food->addCategory($category);
        }

        return null;
    }

    /** @OA\Get(path="/api/food", summary="Lister les plats",
     *     @OA\Response(response=200, description="Liste des plats")) */
    #[Route(methods: 'GET')]
    public function list(): JsonResponse
    {
        return new JsonResponse(array_map(
            fn (Food $food) => $this->toArray($food),
            $this->repository->findBy([], ['id' => 'ASC']),
        ));
    }

    /** @OA\Get(path="/api/food/{id}", summary="Afficher un plat",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Plat trouvé"),
     *     @OA\Response(response=404, description="Plat non trouvé")) */
    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): JsonResponse
    {
        $food = $this->repository->find($id);

        return $food ? new JsonResponse($this->toArray($food)) : $this->notFound();
    }

    /** @OA\Post(path="/api/food", summary="Créer un plat (admin)",
     *     @OA\RequestBody(required=true, @OA\JsonContent(type="object",
     *         @OA\Property(property="title", type="string", example="Tartare de saumon"),
     *         @OA\Property(property="description", type="string", example="Saumon, avocat, citron vert"),
     *         @OA\Property(property="price", type="integer", example=14),
     *         @OA\Property(property="categories", type="array", @OA\Items(type="integer"), example={1}))),
     *     @OA\Response(response=201, description="Plat créé")) */
    #[Route(methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $food = (new Food())->setCreatedAt(new DateTimeImmutable());
        if ($error = $this->fill($food, $this->body($request))) {
            return $this->fail($error);
        }

        $this->manager->persist($food);
        $this->manager->flush();

        return new JsonResponse($this->toArray($food), Response::HTTP_CREATED);
    }

    /** @OA\Put(path="/api/food/{id}", summary="Modifier un plat (admin)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(type="object",
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="price", type="integer"),
     *         @OA\Property(property="categories", type="array", @OA\Items(type="integer")))),
     *     @OA\Response(response=204, description="Plat modifié"),
     *     @OA\Response(response=404, description="Plat non trouvé")) */
    #[Route('/{id}', name: 'edit', methods: 'PUT')]
    public function edit(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $food = $this->repository->find($id);
        if (!$food) {
            return $this->notFound();
        }
        if ($error = $this->fill($food, $this->body($request))) {
            return $this->fail($error);
        }

        $food->setUpdatedAt(new DateTimeImmutable());
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** @OA\Delete(path="/api/food/{id}", summary="Supprimer un plat (admin)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Plat supprimé"),
     *     @OA\Response(response=404, description="Plat non trouvé")) */
    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $food = $this->repository->find($id);
        if (!$food) {
            return $this->notFound();
        }

        $this->manager->remove($food);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
