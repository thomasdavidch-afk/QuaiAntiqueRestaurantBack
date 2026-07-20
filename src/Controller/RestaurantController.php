<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Annotations as OA;

#[Route('/api/restaurant', name: 'app_api_restaurant_')]
class RestaurantController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private RestaurantRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route(name: 'new', methods: ['POST'])]
    /** @OA\Post(
     *     path="/api/restaurant",
     *     summary="Création d'un nouveau restaurant",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du restaurant à créer",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Nom du restaurant"),
     *             @OA\Property(property="description", type="string", example="Description du restaurant"),
     *             @OA\Property(property="amOpeningTime", type="array", @OA\Items(type="string", example="08:00")),
     *             @OA\Property(property="pmOpeningTime", type="array", @OA\Items(type="string", example="12:00")),
     *             @OA\Property(property="maxGuest", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Restaurant créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du restaurant"),
     *             @OA\Property(property="description", type="string", example="Description du restaurant"),
     *             @OA\Property(property="amOpeningTime", type="array", @OA\Items(type="string", example="08:00")),
     *             @OA\Property(property="pmOpeningTime", type="array", @OA\Items(type="string", example="12:00")),
     *             @OA\Property(property="maxGuest", type="integer", example=50)
     *         )
     *     ),
     *  )
     */
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getRestaurant()) {
            return new JsonResponse(['error' => 'User already has a restaurant'], Response::HTTP_BAD_REQUEST);
        }

        $restaurant = $this->serializer->deserialize(
            $request->getContent(),
            Restaurant::class,
            'json'
        );

        $restaurant->setOwner($user);

        $this->manager->persist($restaurant);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $restaurant,
            'json',
            [
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]
        );

        $location = $this->urlGenerator->generate(
            'app_api_restaurant_show',
            ['id' => $restaurant->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse($responseData, Response::HTTP_CREATED, [
            'Location' => $location
        ], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    /** @OA\Get(
     *     path="/api/restaurant/{id}",
     *     summary="Obtenir les détails d'un restaurant",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du restaurant",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du restaurant",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du restaurant"),
     *             @OA\Property(property="description", type="string", example="Description du restaurant"),
     *             @OA\Property(property="amOpeningTime", type="array", @OA\Items(type="string", example="08:00")),
     *             @OA\Property(property="pmOpeningTime", type="array", @OA\Items(type="string", example="12:00")),
     *             @OA\Property(property="maxGuest", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Restaurant non trouvé"
     *     )
     * )
    */
    public function show(int $id): JsonResponse
    {
        $restaurant = $this->repository->find($id);

        if (!$restaurant) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $responseData = $this->serializer->serialize(
            $restaurant,
            'json',
            [
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]
        );

        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    /** @OA\Put(
     *     path="/api/restaurant/{id}",
     *     summary="Modifier un restaurant",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du restaurant",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du restaurant à modifier",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Nom du restaurant"),
     *             @OA\Property(property="description", type="string", example="Description du restaurant"),
     *             @OA\Property(property="amOpeningTime", type="array", @OA\Items(type="string", example="08:00")),
     *             @OA\Property(property="pmOpeningTime", type="array", @OA\Items(type="string", example="12:00")),
     *             @OA\Property(property="maxGuest", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Restaurant mis à jour avec succès"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Restaurant non trouvé"
     *     )
     * )
    */
    #[IsGranted('ROLE_USER')]
    public function edit(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $restaurant = $this->repository->find($id);

        if (!$restaurant) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        if ($restaurant->getOwner() !== $user) {
            return new JsonResponse(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $this->serializer->deserialize(
            $request->getContent(),
            Restaurant::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $restaurant]
        );

        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    /** @OA\Delete(
     *     path="/api/restaurant/{id}",
     *     summary="Supprimer un restaurant",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du restaurant",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Restaurant supprimé avec succès"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Restaurant non trouvé"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé"
     *    )
     * )
    */
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $restaurant = $this->repository->find($id);

        if (!$restaurant) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        if ($restaurant->getOwner() !== $user) {
            return new JsonResponse(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $this->manager->remove($restaurant);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
