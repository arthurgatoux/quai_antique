<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Repository\{CategoryRepository, MenuRepository, RestaurantRepository};
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/menu', name: 'app_api_menu_')]
class MenuController extends ApiController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private MenuRepository $repository,
        private CategoryRepository $categoryRepository,
        private RestaurantRepository $restaurantRepository,
    ) {
    }

    private function toArray(Menu $menu): array
    {
        return [
            'id' => $menu->getId(),
            'title' => $menu->getTitle(),
            'description' => $menu->getDescription(),
            'price' => $menu->getPrice(),
            'categories' => array_map(static fn ($category) => [
                'id' => $category->getId(),
                'title' => $category->getTitle(),
            ], $menu->getCategories()->toArray()),
        ];
    }

    /** Remplit le menu depuis le JSON. Retourne un message d'erreur, ou null si tout est valide. */
    private function fill(Menu $menu, array $data): ?string
    {
        $title = $this->text($data, 'title', 64);
        $description = $this->text($data, 'description', 5000);
        $price = $this->intValue($data, 'price', 0, 32767);

        if ($title === null || $description === null || $price === null) {
            return 'Titre (64 caractères max), description et prix (entier positif) sont obligatoires.';
        }

        $menu->setTitle($title)->setDescription($description)->setPrice($price);

        foreach ($menu->getCategories()->toArray() as $category) {
            $menu->removeCategory($category);
        }
        foreach ($this->idList($data, 'categories') as $categoryId) {
            $category = $this->categoryRepository->find($categoryId);
            if (!$category) {
                return "La catégorie $categoryId n'existe pas.";
            }
            $menu->addCategory($category);
        }

        return null;
    }

    /** @OA\Get(path="/api/menu", summary="Lister les menus",
     *     @OA\Response(response=200, description="Liste des menus")) */
    #[Route(methods: 'GET')]
    public function list(): JsonResponse
    {
        return new JsonResponse(array_map(
            fn (Menu $menu) => $this->toArray($menu),
            $this->repository->findBy([], ['price' => 'ASC', 'id' => 'ASC']),
        ));
    }

    /** @OA\Get(path="/api/menu/{id}", summary="Afficher un menu",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Menu trouvé"),
     *     @OA\Response(response=404, description="Menu non trouvé")) */
    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): JsonResponse
    {
        $menu = $this->repository->find($id);

        return $menu ? new JsonResponse($this->toArray($menu)) : $this->notFound();
    }

    /** @OA\Post(path="/api/menu", summary="Créer un menu (admin)",
     *     @OA\RequestBody(required=true, @OA\JsonContent(type="object",
     *         @OA\Property(property="title", type="string", example="Formule Midi"),
     *         @OA\Property(property="description", type="string", example="Entrée + Plat ou Plat + Dessert"),
     *         @OA\Property(property="price", type="integer", example=22),
     *         @OA\Property(property="categories", type="array", @OA\Items(type="integer"), example={1, 2}))),
     *     @OA\Response(response=201, description="Menu créé"),
     *     @OA\Response(response=409, description="Aucun restaurant en base")) */
    #[Route(methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $restaurant = $this->restaurantRepository->findOneBy([], ['id' => 'ASC']);
        if (!$restaurant) {
            return $this->fail("Aucun restaurant n'existe en base.", Response::HTTP_CONFLICT);
        }

        $menu = (new Menu())->setRestaurant($restaurant)->setCreatedAt(new DateTimeImmutable());
        if ($error = $this->fill($menu, $this->body($request))) {
            return $this->fail($error);
        }

        $this->manager->persist($menu);
        $this->manager->flush();

        return new JsonResponse($this->toArray($menu), Response::HTTP_CREATED);
    }

    /** @OA\Put(path="/api/menu/{id}", summary="Modifier un menu (admin)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(type="object",
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="price", type="integer"),
     *         @OA\Property(property="categories", type="array", @OA\Items(type="integer")))),
     *     @OA\Response(response=204, description="Menu modifié"),
     *     @OA\Response(response=404, description="Menu non trouvé")) */
    #[Route('/{id}', name: 'edit', methods: 'PUT')]
    public function edit(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $menu = $this->repository->find($id);
        if (!$menu) {
            return $this->notFound();
        }
        if ($error = $this->fill($menu, $this->body($request))) {
            return $this->fail($error);
        }

        $menu->setUpdatedAt(new DateTimeImmutable());
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** @OA\Delete(path="/api/menu/{id}", summary="Supprimer un menu (admin)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Menu supprimé"),
     *     @OA\Response(response=404, description="Menu non trouvé")) */
    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $menu = $this->repository->find($id);
        if (!$menu) {
            return $this->notFound();
        }

        $this->manager->remove($menu);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
