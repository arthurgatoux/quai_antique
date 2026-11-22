<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/category', name: 'app_api_category_')]
class CategoryController extends ApiController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private CategoryRepository $repository,
    ) {
    }

    public static function toArray(Category $category): array
    {
        $foods = $category->getFoods()->toArray();
        usort($foods, static fn ($a, $b) => $a->getId() <=> $b->getId());

        return [
            'id' => $category->getId(),
            'title' => $category->getTitle(),
            'foods' => array_map(static fn ($food) => [
                'id' => $food->getId(),
                'title' => $food->getTitle(),
                'description' => $food->getDescription(),
                'price' => $food->getPrice(),
            ], $foods),
        ];
    }

    /** @OA\Get(path="/api/category", summary="Lister les catégories avec leurs plats",
     *     @OA\Response(response=200, description="Liste des catégories")) */
    #[Route(methods: 'GET')]
    public function list(): JsonResponse
    {
        $categories = $this->repository->findBy([], ['id' => 'ASC']);

        return new JsonResponse(array_map([self::class, 'toArray'], $categories));
    }

    /** @OA\Get(path="/api/category/{id}", summary="Afficher une catégorie",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Catégorie trouvée"),
     *     @OA\Response(response=404, description="Catégorie non trouvée")) */
    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): JsonResponse
    {
        $category = $this->repository->find($id);

        return $category ? new JsonResponse(self::toArray($category)) : $this->notFound();
    }

    /** @OA\Post(path="/api/category", summary="Créer une catégorie (admin)",
     *     @OA\RequestBody(required=true, @OA\JsonContent(type="object",
     *         @OA\Property(property="title", type="string", example="Desserts"))),
     *     @OA\Response(response=201, description="Catégorie créée")) */
    #[Route(methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $title = $this->text($this->body($request), 'title', 64);
        if ($title === null) {
            return $this->fail('Le titre est obligatoire (64 caractères maximum).');
        }

        $category = (new Category())->setTitle($title)->setCreatedAt(new DateTimeImmutable());
        $this->manager->persist($category);
        $this->manager->flush();

        return new JsonResponse(self::toArray($category), Response::HTTP_CREATED);
    }

    /** @OA\Put(path="/api/category/{id}", summary="Modifier une catégorie (admin)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(type="object",
     *         @OA\Property(property="title", type="string", example="Plats"))),
     *     @OA\Response(response=204, description="Catégorie modifiée"),
     *     @OA\Response(response=404, description="Catégorie non trouvée")) */
    #[Route('/{id}', name: 'edit', methods: 'PUT')]
    public function edit(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = $this->repository->find($id);
        if (!$category) {
            return $this->notFound();
        }

        $title = $this->text($this->body($request), 'title', 64);
        if ($title === null) {
            return $this->fail('Le titre est obligatoire (64 caractères maximum).');
        }

        $category->setTitle($title)->setUpdatedAt(new DateTimeImmutable());
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** @OA\Delete(path="/api/category/{id}", summary="Supprimer une catégorie (admin)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Catégorie supprimée"),
     *     @OA\Response(response=404, description="Catégorie non trouvée")) */
    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = $this->repository->find($id);
        if (!$category) {
            return $this->notFound();
        }

        $this->manager->remove($category);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
