<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Repository\{PictureRepository, RestaurantRepository};
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/picture', name: 'app_api_picture_')]
class PictureController extends ApiController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private PictureRepository $repository,
        private RestaurantRepository $restaurantRepository,
    ) {
    }

    private function toArray(Picture $picture): array
    {
        return [
            'id' => $picture->getId(),
            'title' => $picture->getTitle(),
            'slug' => $picture->getSlug(),
        ];
    }

    /** @OA\Get(path="/api/picture", summary="Lister les images de la galerie",
     *     @OA\Response(response=200, description="Liste des images")) */
    #[Route(methods: 'GET')]
    public function list(): JsonResponse
    {
        return new JsonResponse(array_map(
            fn (Picture $picture) => $this->toArray($picture),
            $this->repository->findBy([], ['id' => 'ASC']),
        ));
    }

    /** @OA\Get(path="/api/picture/{id}", summary="Afficher une image",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Image trouvée"),
     *     @OA\Response(response=404, description="Image non trouvée")) */
    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): JsonResponse
    {
        $picture = $this->repository->find($id);

        return $picture ? new JsonResponse($this->toArray($picture)) : $this->notFound();
    }

    /** @OA\Post(path="/api/picture", summary="Ajouter une image à la galerie (admin)",
     *     @OA\RequestBody(required=true, @OA\JsonContent(type="object",
     *         @OA\Property(property="title", type="string", example="Nos saumons"),
     *         @OA\Property(property="slug", type="string", example="saumon.png"))),
     *     @OA\Response(response=201, description="Image ajoutée"),
     *     @OA\Response(response=409, description="Aucun restaurant en base")) */
    #[Route(methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $restaurant = $this->restaurantRepository->findOneBy([], ['id' => 'ASC']);
        if (!$restaurant) {
            return $this->fail("Aucun restaurant n'existe en base.", Response::HTTP_CONFLICT);
        }

        $data = $this->body($request);
        $title = $this->text($data, 'title', 128);
        $slug = $this->text($data, 'slug', 128);
        if ($title === null || $slug === null) {
            return $this->fail('Le titre et le nom du fichier image sont obligatoires (128 caractères max).');
        }

        $picture = (new Picture())
            ->setTitle($title)
            ->setSlug($slug)
            ->setRestaurant($restaurant)
            ->setCreatedAt(new DateTimeImmutable());
        $this->manager->persist($picture);
        $this->manager->flush();

        return new JsonResponse($this->toArray($picture), Response::HTTP_CREATED);
    }

    /** @OA\Put(path="/api/picture/{id}", summary="Modifier une image (admin)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(type="object",
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="slug", type="string"))),
     *     @OA\Response(response=204, description="Image modifiée"),
     *     @OA\Response(response=404, description="Image non trouvée")) */
    #[Route('/{id}', name: 'edit', methods: 'PUT')]
    public function edit(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $picture = $this->repository->find($id);
        if (!$picture) {
            return $this->notFound();
        }

        $data = $this->body($request);
        $title = $this->text($data, 'title', 128);
        $slug = $this->text($data, 'slug', 128);
        if ($title === null || $slug === null) {
            return $this->fail('Le titre et le nom du fichier image sont obligatoires (128 caractères max).');
        }

        $picture->setTitle($title)->setSlug($slug)->setUpdatedAt(new DateTimeImmutable());
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** @OA\Delete(path="/api/picture/{id}", summary="Supprimer une image (admin)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Image supprimée"),
     *     @OA\Response(response=404, description="Image non trouvée")) */
    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $picture = $this->repository->find($id);
        if (!$picture) {
            return $this->notFound();
        }

        $this->manager->remove($picture);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
