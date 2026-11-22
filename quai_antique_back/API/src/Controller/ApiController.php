<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};

/** Outils communs aux contrôleurs de l'API (lecture du JSON, validation simple, réponses d'erreur). */
abstract class ApiController extends AbstractController
{
    protected function body(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : [];
    }

    protected function fail(string $message, int $status = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return new JsonResponse(['message' => $message], $status);
    }

    protected function notFound(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    /** Retourne la chaîne nettoyée, ou null si elle est absente, vide ou trop longue. */
    protected function text(array $data, string $key, int $max): ?string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);

        return ($value === '' || mb_strlen($value) > $max) ? null : $value;
    }

    /** Retourne l'entier si la valeur est un entier (ou une chaîne numérique) compris entre $min et $max. */
    protected function intValue(array $data, string $key, int $min, int $max): ?int
    {
        $value = $data[$key] ?? null;
        if (is_string($value) && ctype_digit($value)) {
            $value = (int) $value;
        }

        return (is_int($value) && $value >= $min && $value <= $max) ? $value : null;
    }

    /** @return int[] identifiants uniques extraits d'un tableau JSON */
    protected function idList(array $data, string $key): array
    {
        $ids = $data[$key] ?? [];
        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn ($id) => is_numeric($id) ? (int) $id : 0, $ids),
            static fn (int $id) => $id > 0,
        )));
    }
}
